<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, Resume};

class ResumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $resumes = Resume::where('user_id', $user->id)->get();

        $formattedData = $resumes->map(function ($resume) {
            return [
                "id" => $resume->id,
                "original_filename" => $resume->original_filename,
                "mime_type" => $resume->mime_type,
                "size" => $resume->size,
                "is_current" => $resume->is_current
            ];
        });

        return response()->json([
            "success" => true,
            "message" => "Resumes retrieved successfully.",
            "data" => $formattedData
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'resume' => 'required|mimes:pdf|max:5120'
        ]);

        $user = $request->user();
        $file = $request->file('resume');

        $filePath = $file->store('resumes');

        if (!$filePath) {
            return response()->json([
                "success" => false,
                "message" => "Server error: Failed to secure and store the resume metadata."
            ], 500);
        }

        Resume::where('user_id', $user->id)->update(['is_current' => false]);


        $resume = Resume::create([
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'is_current' => true,
        ]);

        return response()->json([
            "success" => true,
            "message" => "Resume uploaded successfully.",
            "data" => [
                "id" => $resume->id,
                "original_filename" => $resume->original_filename,
                "mime_type" => $resume->mime_type,
                "size" => $resume->size,
                "is_current" => $resume->is_current
            ]
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $resume = Resume::where('id', $id)->where('user_id', $user->id)->first();

        if (!$resume) {
            return response()->json([
                "success" => false,
                "message" => "Resume not found or access denied."
            ], 404);
        }

        if ($user->job_applications()->where('resume_id', $resume->id)->exists()) {
            return response()->json([
                "success" => false,
                "message" => "Cannot delete resume. It is currently referenced by an active job application."
            ], 409);
        }

        if (Storage::exists($resume->file_path)) {
            Storage::delete($resume->file_path);
        }

        $resume->delete();

        return response()->noContent();
    }


    public function download(Request $request, string $id)
    {
        $user = $request->user();
        $resume = Resume::where('id', $id)->where('user_id', $user->id)->first();

        if (!$resume) {
            return response()->json([
                "success" => false,
                "message" => "Resume not found or access denied."
            ], 404);
        }

        if (!Storage::exists($resume->file_path)) {
            return response()->json([
                "success" => false,
                "message" => "The requested file could not be found on the server."
            ], 404);
        }

        return response()->download(
            storage_path('app/' . $resume->file_path), 
            $resume->original_filename, 
            [
                'Content-Type' => $resume->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $resume->original_filename . '"'
            ]
        );
    }

}
