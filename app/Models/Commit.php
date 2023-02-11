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

}
