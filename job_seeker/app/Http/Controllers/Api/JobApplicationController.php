<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobApplicationController extends Controller
{
    public function apply(Request $request, $id)
    {
        $user = $request->user();
        abort_if($user->role !== 'job_seeker', 403, 'Only job seekers can apply.');

        $vacancy = JobVacancy::findOrFail($id);

        abort_if(
            $vacancy->status !== 'published' || ($vacancy->deadline && $vacancy->deadline->isPast()),
            409,
            'Vacancy is closed or deadline has passed.'
        );

        $alreadyApplied = JobApplication::where('user_id', $user->id)
            ->where('job_vacancy_id', $vacancy->id)
            ->exists();
        abort_if($alreadyApplied, 409, 'You have already applied for this vacancy.');

        $validated = $request->validate([
            'resume_id' => [
                'required',
                Rule::exists('resumes', 'id')->where('user_id', $user->id),
            ],
            'cover_letter' => 'nullable|string|max:2000',
        ]);

        $application = JobApplication::create([
            'user_id' => $user->id,
            'job_vacancy_id' => $vacancy->id,
            'resume_id' => $validated['resume_id'],
            'cover_letter' => $validated['cover_letter'] ?? null,
            'status' => 'submitted',
            'applied_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'data' => $application,
        ], 201);
    }

    public function myApplications(Request $request)
    {
        $query = $request->user()->job_applications()->with('job_vacancy');

        if ($request->filled('status')) {
            $request->validate(['status' => 'in:submitted,reviewing,accepted,rejected']);
            $query->where('status', $request->query('status'));
        }

        $perPage = (int) $request->query('per_page', 10);
        $applications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Applications retrieved successfully.',
            'data' => $applications->items(),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ], 200);
    }

    public function myApplicationDetail(Request $request, $id)
    {
        $application = JobApplication::with(['job_vacancy', 'resume'])->findOrFail($id);

        abort_if($application->user_id !== $request->user()->id, 403, 'Unauthorized.');

        return response()->json([
            'success' => true,
            'message' => 'Application retrieved successfully.',
            'data' => $application,
        ], 200);
    }

    public function adminIndex(Request $request)
    {
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized.');

        $query = JobApplication::with(['user', 'job_vacancy', 'resume']);

        if ($request->filled('status')) {
            $request->validate(['status' => 'in:submitted,reviewing,accepted,rejected']);
            $query->where('status', $request->query('status'));
        }

        $perPage = (int) $request->query('per_page', 10);
        $applications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Applications retrieved successfully.',
            'data' => $applications->items(),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized.');

        $application = JobApplication::findOrFail($id);

        abort_if(
            in_array($application->status, ['accepted', 'rejected']),
            409,
            'Application status has already been finalized.'
        );

        $validated = $request->validate([
            'status' => 'required|in:submitted,reviewing,accepted,rejected',
        ]);

        $application->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated successfully.',
            'data' => [
                'id' => $application->id,
                'status' => $application->status,
            ],
        ], 200);
    }
}
