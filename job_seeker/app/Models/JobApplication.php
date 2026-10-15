<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;

#[Guarded(['id'])]
class JobApplication extends Model
{
     public function user() {
        return $this->belongsTo(User::class);
    }
    public function resume() {
        return $this->belongsTo(Resume::class);
    }
    public function job_vacanies() {
        return $this->belongsTo(JobVacanies::class);
    }
}
