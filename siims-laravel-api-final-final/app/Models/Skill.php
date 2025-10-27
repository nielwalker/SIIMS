<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;


// This Skill belong to a many Work Posts
public function workPosts() {
    return $this->belongsToMany(WorkPost::class, 'work_post_skills');
}

        
}
