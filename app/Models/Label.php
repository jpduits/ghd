<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Label extends HasDateModel
{
    use HasFactory;

    protected $table = 'labels';

    public $timestamps = false;

    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'issues_labels', 'label_id', 'issue_id');
    }

}
