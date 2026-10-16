<?php

use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

// 1. POST /api/v1/auth/register
test('01. POST /api/v1/auth/register creates user and returns 201', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Alice Test',
        'email' => 'alice@test.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Alice Test',
                'email' => 'alice@test.com',
                'role' => 'job_seeker',
            ],
        ]);
});

// 2. POST /api/v1/auth/login
test('02. POST /api/v1/auth/login returns bearer token', function () {
    $user = User::factory()->create([
        'email' => 'login_user@test.com',
        'password' => bcrypt('password123'),
        'role' => 'job_seeker',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login_user@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['access_token', 'token_type', 'user'],
        ]);
});

// 3. GET /api/v1/jobs
test('03. GET /api/v1/jobs lists only published jobs', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Published Job',
        'company_name' => 'PT Test',
        'location' => 'Jakarta',
        'employment_type' => 'full_time',
        'description' => 'Test Desc',
        'status' => 'published',
        'deadline' => now()->addDays(5),
    ]);

    JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Draft Job',
        'company_name' => 'PT Test',
        'location' => 'Jakarta',
        'employment_type' => 'full_time',
        'description' => 'Test Desc',
        'status' => 'draft',
        'deadline' => now()->addDays(5),
    ]);

    $response = $this->getJson('/api/v1/jobs');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Published Job');
});

// 4. GET /api/v1/jobs/{id}
test('04. GET /api/v1/jobs/{id} retrieves published job detail and 404 for draft', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $published = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Backend Engineer',
        'company_name' => 'PT Alpha',
        'location' => 'Surabaya',
        'employment_type' => 'contract',
        'description' => 'Great job',
        'status' => 'published',
        'deadline' => now()->addDays(10),
    ]);

    $draft = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Hidden Job',
        'company_name' => 'PT Alpha',
        'location' => 'Surabaya',
        'employment_type' => 'contract',
        'description' => 'Hidden job',
        'status' => 'draft',
    ]);

    $this->getJson('/api/v1/jobs/' . $published->id)
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Backend Engineer');

    $this->getJson('/api/v1/jobs/' . $draft->id)
        ->assertStatus(404);
});

// 5. GET /api/v1/me/profile
test('05. GET /api/v1/me/profile returns seeker profile', function () {
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    $response = $this->actingAs($seeker)->getJson('/api/v1/me/profile');

    $response->assertStatus(200)
        ->assertJsonPath('data.user_id', $seeker->id);
});

// 6. PATCH /api/v1/me/profile
test('06. PATCH /api/v1/me/profile updates profile text fields', function () {
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    $response = $this->actingAs($seeker)->patchJson('/api/v1/me/profile', [
        'phone' => '08123456789',
        'address' => 'Jl. Merdeka No. 1',
        'summary' => 'Junior Web Developer',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.phone', '08123456789')
        ->assertJsonPath('data.address', 'Jl. Merdeka No. 1')
        ->assertJsonPath('data.summary', 'Junior Web Developer');
});

// 7. POST /api/v1/me/resumes
test('07. POST /api/v1/me/resumes uploads resume PDF and returns 201', function () {
    $seeker = User::factory()->create(['role' => 'job_seeker']);
    $file = UploadedFile::fake()->create('curriculum_vitae.pdf', 300, 'application/pdf');

    $response = $this->actingAs($seeker)->postJson('/api/v1/me/resumes', [
        'resume' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.original_filename', 'curriculum_vitae.pdf')
        ->assertJsonPath('data.is_current', true);
});

// 8. GET /api/v1/me/resumes
test('08. GET /api/v1/me/resumes lists user resumes', function () {
    $seeker = User::factory()->create(['role' => 'job_seeker']);
    $seeker->resumes()->create([
        'original_filename' => 'cv_1.pdf',
        'storage_path' => 'resumes/cv_1.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'is_current' => true,
    ]);

    $response = $this->actingAs($seeker)->getJson('/api/v1/me/resumes');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.original_filename', 'cv_1.pdf');
});

// 9. GET /api/v1/me/resumes/{id}/download
test('09. GET /api/v1/me/resumes/{id}/download streams resume to owner', function () {
    $seeker = User::factory()->create(['role' => 'job_seeker']);
    $otherSeeker = User::factory()->create(['role' => 'job_seeker']);

    Storage::disk('local')->put('resumes/' . $seeker->id . '/test.pdf', 'dummy content');

    $resume = $seeker->resumes()->create([
        'original_filename' => 'test.pdf',
        'storage_path' => 'resumes/' . $seeker->id . '/test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 13,
        'is_current' => true,
    ]);

    // Forbidden for other seeker
    $this->actingAs($otherSeeker)
        ->getJson('/api/v1/me/resumes/' . $resume->id . '/download')
        ->assertStatus(403);

    // Success for owner
    $this->actingAs($seeker)
        ->get('/api/v1/me/resumes/' . $resume->id . '/download')
        ->assertStatus(200);
});

// 10. POST /api/v1/jobs/{id}/applications
test('10. POST /api/v1/jobs/{id}/applications applies to job and returns 201', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'DevOps',
        'company_name' => 'PT Cloud',
        'location' => 'Bandung',
        'employment_type' => 'full_time',
        'description' => 'Docker & K8s',
        'status' => 'published',
        'deadline' => now()->addDays(20),
    ]);

    $resume = $seeker->resumes()->create([
        'original_filename' => 'devops_cv.pdf',
        'storage_path' => 'resumes/devops.pdf',
        'mime_type' => 'application/pdf',
        'size' => 2048,
        'is_current' => true,
    ]);

    $response = $this->actingAs($seeker)->postJson('/api/v1/jobs/' . $vacancy->id . '/applications', [
        'resume_id' => $resume->id,
        'cover_letter' => 'I am excited to apply.',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'submitted');

    // Duplicate application check -> 409 Conflict
    $this->actingAs($seeker)->postJson('/api/v1/jobs/' . $vacancy->id . '/applications', [
        'resume_id' => $resume->id,
        'cover_letter' => 'Duplicate attempt',
    ])->assertStatus(409);
});

// 11. GET /api/v1/me/applications
test('11. GET /api/v1/me/applications lists user applications', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Frontend',
        'company_name' => 'PT Web',
        'location' => 'Jakarta',
        'employment_type' => 'full_time',
        'description' => 'Vue 3',
        'status' => 'published',
    ]);

    $resume = $seeker->resumes()->create([
        'original_filename' => 'fe_cv.pdf',
        'storage_path' => 'resumes/fe.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'is_current' => true,
    ]);

    JobApplication::create([
        'user_id' => $seeker->id,
        'job_vacancy_id' => $vacancy->id,
        'resume_id' => $resume->id,
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($seeker)->getJson('/api/v1/me/applications');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

// 12. GET /api/v1/me/applications/{id}
test('12. GET /api/v1/me/applications/{id} shows application detail', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);
    $other = User::factory()->create(['role' => 'job_seeker']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'QA',
        'company_name' => 'PT Test',
        'location' => 'Jakarta',
        'employment_type' => 'contract',
        'description' => 'Cypress',
        'status' => 'published',
    ]);

    $resume = $seeker->resumes()->create([
        'original_filename' => 'qa.pdf',
        'storage_path' => 'resumes/qa.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'is_current' => true,
    ]);

    $application = JobApplication::create([
        'user_id' => $seeker->id,
        'job_vacancy_id' => $vacancy->id,
        'resume_id' => $resume->id,
        'status' => 'submitted',
    ]);

    // Forbidden for other seeker
    $this->actingAs($other)
        ->getJson('/api/v1/me/applications/' . $application->id)
        ->assertStatus(403);

    // Success for owner
    $this->actingAs($seeker)
        ->getJson('/api/v1/me/applications/' . $application->id)
        ->assertStatus(200)
        ->assertJsonPath('data.id', $application->id);
});

// 13. DELETE /api/v1/me/resumes/{id}
test('13. DELETE /api/v1/me/resumes/{id} returns 409 if referenced, 204 if unused', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Security',
        'company_name' => 'PT Sec',
        'location' => 'Jakarta',
        'employment_type' => 'full_time',
        'description' => 'SecOps',
        'status' => 'published',
    ]);

    $usedResume = $seeker->resumes()->create([
        'original_filename' => 'used.pdf',
        'storage_path' => 'resumes/used.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'is_current' => true,
    ]);

    JobApplication::create([
        'user_id' => $seeker->id,
        'job_vacancy_id' => $vacancy->id,
        'resume_id' => $usedResume->id,
        'status' => 'submitted',
    ]);

    // Attempting to delete referenced resume -> 409 Conflict
    $this->actingAs($seeker)
        ->deleteJson('/api/v1/me/resumes/' . $usedResume->id)
        ->assertStatus(409);

    // Unused resume delete -> 204 No Content
    $unusedResume = $seeker->resumes()->create([
        'original_filename' => 'unused.pdf',
        'storage_path' => 'resumes/unused.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'is_current' => false,
    ]);

    $this->actingAs($seeker)
        ->deleteJson('/api/v1/me/resumes/' . $unusedResume->id)
        ->assertStatus(204);
});

// 14. POST /api/v1/jobs (Admin)
test('14. POST /api/v1/jobs creates vacancy as admin and returns 201', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    // Forbidden for seeker
    $this->actingAs($seeker)->postJson('/api/v1/jobs', [
        'title' => 'Manager',
        'company_name' => 'PT Corp',
        'location' => 'Jakarta',
        'employment_type' => 'full_time',
        'description' => 'Lead team',
        'status' => 'published',
    ])->assertStatus(403);

    // Success for admin
    $response = $this->actingAs($admin)->postJson('/api/v1/jobs', [
        'title' => 'Project Manager',
        'company_name' => 'PT Nusantara',
        'location' => 'Malang',
        'employment_type' => 'full_time',
        'description' => 'Managing tech projects',
        'status' => 'published',
        'deadline' => now()->addDays(30)->toDateString(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'Project Manager');
});

// 15. PATCH /api/v1/jobs/{id} (Admin)
test('15. PATCH /api/v1/jobs/{id} updates vacancy as admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Old Title',
        'company_name' => 'Old Corp',
        'location' => 'Old City',
        'employment_type' => 'full_time',
        'description' => 'Old Desc',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($admin)->patchJson('/api/v1/jobs/' . $vacancy->id, [
        'title' => 'Updated Title',
        'status' => 'published',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.status', 'published');
});

// 16. DELETE /api/v1/jobs/{id} (Admin)
test('16. DELETE /api/v1/jobs/{id} deletes vacancy if no applications (204)', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Job to delete',
        'company_name' => 'Delete Corp',
        'location' => 'Bali',
        'employment_type' => 'internship',
        'description' => 'Delete test',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/jobs/' . $vacancy->id)
        ->assertStatus(204);
});

// 17. GET /api/v1/admin/applications (Admin)
test('17. GET /api/v1/admin/applications returns all applications for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    // Forbidden for seeker
    $this->actingAs($seeker)
        ->getJson('/api/v1/admin/applications')
        ->assertStatus(403);

    // Success for admin
    $this->actingAs($admin)
        ->getJson('/api/v1/admin/applications')
        ->assertStatus(200);
});

// 18. PATCH /api/v1/admin/applications/{id}/status (Admin)
test('18. PATCH /api/v1/admin/applications/{id}/status updates status and locks terminal state', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);

    $vacancy = JobVacancy::create([
        'created_by' => $admin->id,
        'title' => 'Designer',
        'company_name' => 'PT Design',
        'location' => 'Bandung',
        'employment_type' => 'full_time',
        'description' => 'UI UX',
        'status' => 'published',
    ]);

    $resume = $seeker->resumes()->create([
        'original_filename' => 'design.pdf',
        'storage_path' => 'resumes/design.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'is_current' => true,
    ]);

    $application = JobApplication::create([
        'user_id' => $seeker->id,
        'job_vacancy_id' => $vacancy->id,
        'resume_id' => $resume->id,
        'status' => 'submitted',
    ]);

    // Update to reviewing
    $this->actingAs($admin)->patchJson('/api/v1/admin/applications/' . $application->id . '/status', [
        'status' => 'reviewing',
    ])->assertStatus(200)
      ->assertJsonPath('data.status', 'reviewing');

    // Update to accepted (terminal state)
    $this->actingAs($admin)->patchJson('/api/v1/admin/applications/' . $application->id . '/status', [
        'status' => 'accepted',
    ])->assertStatus(200)
      ->assertJsonPath('data.status', 'accepted');

    // Attempting to change accepted status -> 409 Conflict
    $this->actingAs($admin)->patchJson('/api/v1/admin/applications/' . $application->id . '/status', [
        'status' => 'rejected',
    ])->assertStatus(409);
});
