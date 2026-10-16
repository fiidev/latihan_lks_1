<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        $resumes = $request->user()->resumes()->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Resumes retrieved successfully.',
            'data' => $resumes,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf|max:5120',
        ]);

        $file = $request->file('resume');
        $user = $request->user();

        $user->resumes()->update(['is_current' => false]);

        $path = $file->store('resumes/'.$user->id, 'local');

        $resume = $user->resumes()->create([
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'is_current' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resume uploaded successfully.',
            'data' => $resume,
        ], 201);
    }

    public function download(Request $request, $id)
    {
        $resume = Resume::findOrFail($id);

        abort_if($resume->user_id !== $request->user()->id, 403, 'Unauthorized access to resume.');

        abort_if(! Storage::disk('local')->exists($resume->storage_path), 404, 'File not found on storage.');

        return Storage::disk('local')->download($resume->storage_path, $resume->original_filename);
    }

    public function destroy(Request $request, $id)
    {
        $resume = Resume::findOrFail($id);

        abort_if($resume->user_id !== $request->user()->id, 403, 'Unauthorized.');

        abort_if($resume->job_applications()->exists(), 409, 'Resume is referenced by application and cannot be deleted.');

        if (Storage::disk('local')->exists($resume->storage_path)) {
            Storage::disk('local')->delete($resume->storage_path);
        }

        $resume->delete();

        return response()->noContent();
    }
}
