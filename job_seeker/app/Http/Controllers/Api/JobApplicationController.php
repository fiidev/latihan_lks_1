<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobVacancy;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $job = JobVacancy::all();

        if (!$job) {
            return response()->json([
                "success" => true,
                "messages" => "Jobs retrieved successfully",
                "data" => []
            ], 200);
        }

        return response()->json([
            "success" => true,
            "messages" => "Jobs retrieved successfully",
            "data" => [
                $job
            ]
        ]);
        
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $job = JobVacancy::find($id);

        if (!$job) {
            return response()->json([
                "success" => false,
                "messages" => "Jobs documented non-public or doesn't exist",
                "data" => []
            ], 200);
        }

        return response()->json([
            "success" => true,
            "messages" => "Jobs retrieved successfully",
            "data" => [
                $job
            ]
        ]);
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
    public function destroy(string $id)
    {
        //
    }
}
