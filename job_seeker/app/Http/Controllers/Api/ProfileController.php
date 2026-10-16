<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeekerProfile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $profile = SeekerProfile::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['phone' => null, 'address' => null, 'summary' => null]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => $profile,
        ], 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'summary' => 'nullable|string|max:255',
        ]);

        $profile = SeekerProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $profile,
        ], 200);
    }
}
