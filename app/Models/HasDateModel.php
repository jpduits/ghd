<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class HasDateModel extends Model
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->toISOString(true);
    }
}
