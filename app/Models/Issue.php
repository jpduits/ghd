<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Issue extends HasDateModel
{
    use HasFactory;

    protected $table = 'issues';

    public $incrementing = false;

    public $timestamps = false;

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'issues_labels', 'issue_id', 'label_id');
    }
}
