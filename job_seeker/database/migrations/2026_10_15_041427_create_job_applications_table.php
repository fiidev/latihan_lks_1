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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained('resumes')->restrictOnDelete();
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['submitted', 'reviewing', 'accepted', 'rejected'])->default('submitted');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'job_vacancy_id']);
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
