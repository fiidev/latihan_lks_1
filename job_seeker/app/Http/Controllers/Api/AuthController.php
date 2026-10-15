<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required:string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
        ]);

        // if ($validate['password'] !== $validate['password_confirmation']) {
        //     return response()->json([
        //         "success" => false,
        //         "message" => "password_confirmation"
        //     ], 422);
        // }

        $user = User::create([
            'name' => $validate['name'],
            'email' => $validate['email'],
            'password' => $validate['password'],
        ]);

        $user->seeker_profile()->create([
            'phone' => '-',
            'address' => '-',
            'summary' => '-',
        ]);

        $user->refresh();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "login success",
            "data" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "role" => $user->role,
            ]
        ], 200);
    }

    public function login(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
           
            return response()->json([
                "success" => false,
                "message" => "Incorrect Credentials",
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "login success",
            "data" => [
                "access_token" => $token,
                "token_type" => "Bearer",
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "role" => $user->role,
                ]
            ]
        ], 200);
    }
}
