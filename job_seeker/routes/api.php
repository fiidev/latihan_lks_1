<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobApplicationController;
use App\Http\Controllers\Api\JobVacancyController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResumeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // 1. Auth Publik
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // 2. Lowongan Publik
    Route::get('/jobs', [JobVacancyController::class, 'index']);
    Route::get('/jobs/{id}', [JobVacancyController::class, 'show']);

    // 3. Authenticated Sanctum
    Route::middleware('auth:sanctum')->group(function () {

        // Profile
        Route::get('/me/profile', [ProfileController::class, 'index']);
        Route::patch('/me/profile', [ProfileController::class, 'update']);

        // Resumes
        Route::post('/me/resumes', [ResumeController::class, 'store']);
        Route::get('/me/resumes', [ResumeController::class, 'index']);
        Route::get('/me/resumes/{id}/download', [ResumeController::class, 'download']);
        Route::delete('/me/resumes/{id}', [ResumeController::class, 'destroy']);

        // Applications (Pelamar)
        Route::post('/jobs/{id}/applications', [JobApplicationController::class, 'apply']);
        Route::get('/me/applications', [JobApplicationController::class, 'myApplications']);
        Route::get('/me/applications/{id}', [JobApplicationController::class, 'myApplicationDetail']);

        // Admin Vacancies
        Route::post('/jobs', [JobVacancyController::class, 'store']);
        Route::patch('/jobs/{id}', [JobVacancyController::class, 'update']);
        Route::delete('/jobs/{id}', [JobVacancyController::class, 'destroy']);

        // Admin Applications
        Route::get('/admin/applications', [JobApplicationController::class, 'adminIndex']);
        Route::patch('/admin/applications/{id}/status', [JobApplicationController::class, 'updateStatus']);
    });
});
