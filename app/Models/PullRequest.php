<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PullRequest extends Model
{
    use HasFactory;

    protected $table = 'pull_requests';

    public $timestamps = false;

    public $incrementing = false;

    public function baseRepository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function baseUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function headRepository()
    {
        return $this->belongsTo(Repository::class, 'head_repository_id', 'id');
    }

    public function headUser()
    {
        return $this->belongsTo(User::class, 'head_user_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function mergeCommit()
    {
        return $this->hasOne(Commit::class, 'id', 'merge_commit_id');
    }

    public function commits()
    {
        return $this->belongsToMany(Commit::class, 'pull_request_commits', 'pull_request_id', 'commit_id');
    }

}
