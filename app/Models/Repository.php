<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Repository extends Model
{
    use HasFactory;

    protected $table = 'repositories';

    //public $incrementing = false;

    public $timestamps = false;

    public function commits()
    {
        return $this->hasMany(Commit::class, 'repository_id', 'id');
    }

    public function stargazers()
    {
        return $this->hasMany(Stargazer::class, 'repository_id', 'id');
    }

    public function pullRequests()
    {
        return $this->hasMany(PullRequest::class, 'base_repository_id', 'id');
    }

    public function forks()
    {
        return $this->hasMany(Fork::class, 'repository_id', 'id');
    }

    public function issues()
    {
        return $this->hasMany(Issue::class, 'repository_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

}
