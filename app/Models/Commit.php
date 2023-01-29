<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commit extends Model
{
    use HasFactory;

    protected $table = 'commits';

    public $timestamps = false;
}
