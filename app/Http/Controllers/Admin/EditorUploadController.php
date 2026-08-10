<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditorUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120',
            ],
        ]);

        $file = $validated['image'];
        $extension = $file->guessExtension() ?: 'png';
        $filename = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs('editor-images', $filename, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
