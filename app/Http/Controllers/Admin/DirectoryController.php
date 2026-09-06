<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubjectKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDirectionRequest;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Requests\Admin\StoreSchoolClassRequest;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateDirectionRequest;
use App\Http\Requests\Admin\UpdateGroupRequest;
use App\Http\Requests\Admin\UpdateSchoolClassRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Direction;
use App\Models\Group;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\CoreSubjects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/directories/Index', [
            'tab' => $request->string('tab', 'classes')->toString(),
            'classes' => SchoolClass::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order']),
            'subjects' => Subject::query()
                ->with('schoolClasses:id,name')
                ->orderBy('kind')
                ->orderBy('name')
                ->get(['id', 'name', 'kind', 'is_system']),
            'profileSubjects' => Subject::query()
                ->where('kind', SubjectKind::Profile)
                ->orderBy('name')
                ->get(['id', 'name']),
            'directions' => Direction::query()
                ->with(['subjects:id,name'])
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'groups' => Group::query()
                ->with('subject:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'subject_id']),
        ]);
    }

    public function storeClass(StoreSchoolClassRequest $request): RedirectResponse
    {
        SchoolClass::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Class created.')]);

        return to_route('admin.directories.index', ['tab' => 'classes']);
    }

    public function updateClass(UpdateSchoolClassRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Class updated.')]);

        return to_route('admin.directories.index', ['tab' => 'classes']);
    }

    public function destroyClass(SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Class deleted.')]);

        return to_route('admin.directories.index', ['tab' => 'classes']);
    }

    public function storeSubject(StoreSubjectRequest $request): RedirectResponse
    {
        $subject = Subject::query()->create([
            ...$request->safe()->only('name'),
            'kind' => SubjectKind::Profile,
        ]);
        $subject->schoolClasses()->sync($request->validated('school_class_ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject created.')]);

        return to_route('admin.directories.index', ['tab' => 'subjects']);
    }

    public function updateSubject(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validated();
        $wantsCore = $request->boolean('is_core');

        if ($subject->is_system && ! $wantsCore) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Обязательный предмет нельзя перевести в профильный, пока он в шаблоне ЕНТ.']);

            return to_route('admin.directories.index', ['tab' => 'subjects']);
        }

        if ($subject->is_system) {
            $subject->schoolClasses()->sync($validated['school_class_ids']);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject updated.')]);

            return to_route('admin.directories.index', ['tab' => 'subjects']);
        }

        $subject->update(['name' => $validated['name']]);
        $subject->schoolClasses()->sync($validated['school_class_ids']);

        if ($wantsCore) {
            CoreSubjects::markAsCore($subject);
        } elseif ($subject->kind === SubjectKind::Core) {
            CoreSubjects::markAsProfile($subject);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject updated.')]);

        return to_route('admin.directories.index', ['tab' => 'subjects']);
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        if ($subject->is_system) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Системный предмет нельзя удалить.']);

            return to_route('admin.directories.index', ['tab' => 'subjects']);
        }

        $subject->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject deleted.')]);

        return to_route('admin.directories.index', ['tab' => 'subjects']);
    }

    public function storeDirection(StoreDirectionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $direction = Direction::query()->create($request->safe()->only(['code', 'name']));

            $direction->subjects()->sync([
                $request->integer('first_subject_id') => ['position' => 1],
                $request->integer('second_subject_id') => ['position' => 2],
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Направление создано.']);

        return to_route('admin.directories.index', ['tab' => 'directions']);
    }

    public function updateDirection(UpdateDirectionRequest $request, Direction $direction): RedirectResponse
    {
        DB::transaction(function () use ($request, $direction): void {
            $direction->update($request->safe()->only(['code', 'name']));

            $direction->subjects()->sync([
                $request->integer('first_subject_id') => ['position' => 1],
                $request->integer('second_subject_id') => ['position' => 2],
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Направление обновлено.']);

        return to_route('admin.directories.index', ['tab' => 'directions']);
    }

    public function destroyDirection(Direction $direction): RedirectResponse
    {
        if ($direction->exams()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Направление используется в экзаменах.']);

            return to_route('admin.directories.index', ['tab' => 'directions']);
        }

        $direction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Направление удалено.']);

        return to_route('admin.directories.index', ['tab' => 'directions']);
    }

    public function storeGroup(StoreGroupRequest $request): RedirectResponse
    {
        Group::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Group created.')]);

        return to_route('admin.directories.index', ['tab' => 'groups']);
    }

    public function updateGroup(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $group->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Group updated.')]);

        return to_route('admin.directories.index', ['tab' => 'groups']);
    }

    public function destroyGroup(Group $group): RedirectResponse
    {
        $group->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Group deleted.')]);

        return to_route('admin.directories.index', ['tab' => 'groups']);
    }
}
