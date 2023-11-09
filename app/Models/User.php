<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends HasDateModel
{
    use HasFactory;

    protected $table = 'users';

}
