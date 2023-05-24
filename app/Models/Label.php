<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;

    protected $table = 'labels';

    public $timestamps = false;

    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'issues_labels', 'label_id', 'issue_id');
    }

}
