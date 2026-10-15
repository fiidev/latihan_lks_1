<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;

#[Guarded(['id'])]
class SeekerProfile extends Model
{
    public function user() {
        return $this->belongsTo(User::class);
    }
}
