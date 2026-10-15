<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('cover_letter');
            $table->string('status');
            $table->timestamp('applied_at');
            $table->foreignId('user_id')->constrained('users')->unique()->onDelete('cascade');
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->unique()->onDelete('cascade');
            $table->foreignId('resume_id')->constrained('resumes')->unique()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
