<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stargazer extends Model
{
    use HasFactory;

    protected $table = 'stargazers';

    public $timestamps = false;
}
