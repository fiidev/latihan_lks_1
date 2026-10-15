<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController, ProfileController, ResumeController};

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/profile', [ProfileController::class, 'index']);
    Route::patch('/me/profile', [ProfileController::class, 'update']);
    Route::get('/me/resumes', [ResumeController::class, 'index']);
    Route::post('/me/resumes', [ResumeController::class, 'store']);
    Route::get('/me/resumes/{id}/download', [ResumeController::class, 'download']);
    Route::delete('/me/resumes/{id}', [ResumeController::class, 'destroy']);
    });
});
