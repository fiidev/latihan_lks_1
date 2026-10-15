<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, SeekerProfile};

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
         $user = $request->user();
         $profile = $user->seeker_profile();

         if (!$profile->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Profile doesnt exists.',
            ], 404);
        }


        return response()->json([ 
            'success' => true, 
            'message' => 'Profile retrieved successfully', 
            'data' => $profile->get()
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
        //
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
    public function update(Request $request)
    {
        $profile = SeekerProfile::where('user_id', $request->user()->id)->firstOrFail();

        $validate = $request->validate([
            "phone" => "nullable|string",
            "address" => "nullable|string",
            "summary" => "nullable|string|max:225",
            "profile_photo_url" => "nullable|mimes:jpg,png,webp,jpeg"
        ]);
        

        if ($request->hasFile('profile_photo_path')) {
            if ($profile->profile_photo_url && Storage::disk('public')->exist($profile->profile_photo_url)) {
                $validate['profile_photo_url'] = $request->file('profile_photo_path')->store('profile', 'public');
            }
        }

        $profile->update($validate);

        return response()->json([
            "success" => true,
            "message" => "Profile updated successfully.",
            "data"=> [
                "user_id"=> $request->user()->id,
                "phone" => $profile->phone,
                "address"=> $profile->address,
                "summary"=> $profile->summary,
                "profile_photo_url"=> $$profile->profile_photo_url ?? null,
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
