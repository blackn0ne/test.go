<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Requests\Admin\StoreSchoolClassRequest;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateGroupRequest;
use App\Http\Requests\Admin\UpdateSchoolClassRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Group;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    /**
     * Display directories with tabs.
     */
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
                ->orderBy('name')
                ->get(['id', 'name']),
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
        $subject = Subject::query()->create($request->safe()->only('name'));
        $subject->schoolClasses()->sync($request->validated('school_class_ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject created.')]);

        return to_route('admin.directories.index', ['tab' => 'subjects']);
    }

    public function updateSubject(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->safe()->only('name'));
        $subject->schoolClasses()->sync($request->validated('school_class_ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject updated.')]);

        return to_route('admin.directories.index', ['tab' => 'subjects']);
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        $subject->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subject deleted.')]);

        return to_route('admin.directories.index', ['tab' => 'subjects']);
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
