<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;

#[Guarded(['id'])]
class Resume extends Model
{
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function job_applications() {
        return $this->hasMany(JobApplications::class);
    }
}
