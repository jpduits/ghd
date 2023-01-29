<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stargazer extends Model
{
    use HasFactory;

    protected $table = 'stargazers';

    public $timestamps = false;
}
