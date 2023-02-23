<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commit extends Model
{
    use HasFactory;

    protected $table = 'commits';

    public $timestamps = false;

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function committer()
    {
        return $this->belongsTo(User::class, 'committer_id', 'id');
    }

    public function pullRequest()
    {
        return $this->belongsToMany(PullRequest::class, 'pull_requests_commits', 'commit_id', 'pull_request_id', 'id', 'id');
    }


    public function parents()
    {
        return $this->belongsToMany(Commit::class, 'commit_parents', 'commit_id', 'parent_id', 'id', 'id');
    }


}
