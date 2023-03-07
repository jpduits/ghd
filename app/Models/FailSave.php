<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FailSave extends Model
{
    use HasFactory;

    protected $table = 'fail_save';

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

}
