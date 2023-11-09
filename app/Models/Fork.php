<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fork extends HasDateModel
{
    use HasFactory;

    protected $table = 'forks';

    public $timestamps = false;

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }
}
