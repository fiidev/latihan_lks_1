<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use Illuminate\Http\Request;

class JobVacancyController extends Controller
{
    public function index(Request $request)
    {
        $query = JobVacancy::query()->where('status', 'published');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$request->query('location').'%');
        }

        if ($request->filled('employment_type')) {
            $request->validate([
                'employment_type' => 'in:full_time,part_time,contract,internship',
            ]);
            $query->where('employment_type', $request->query('employment_type'));
        }

        $perPage = (int) $request->query('per_page', 10);
        $jobs = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Jobs retrieved successfully.',
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ], 200);
    }

    public function show($id)
    {
        $job = JobVacancy::where('status', 'published')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Job retrieved successfully.',
            'data' => $job,
        ], 200);
    }

    public function store(Request $request)
    {
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,contract,internship',
            'description' => 'required|string',
            'status' => 'required|in:draft,published',
            'deadline' => 'nullable|date|after_or_equal:today',
        ]);

        $job = $request->user()->job_vacancies()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Job vacancy created successfully.',
            'data' => $job,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized.');

        $job = JobVacancy::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'company_name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'employment_type' => 'sometimes|required|in:full_time,part_time,contract,internship',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,published',
            'deadline' => 'nullable|date',
        ]);

        $job->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Job vacancy updated successfully.',
            'data' => $job,
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        abort_if($request->user()->role !== 'admin', 403, 'Unauthorized.');

        $job = JobVacancy::findOrFail($id);

        // Aturan Retensi Bab 6.12: Tolak jika sudah ada pelamar -> 409 Conflict
        abort_if($job->job_applications()->exists(), 409, 'Vacancy has applications and cannot be deleted.');

        $job->delete();

        return response()->noContent();
    }
}
