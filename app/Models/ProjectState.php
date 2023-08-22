<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectState extends Model
{

    protected $table = 'project_states';

    protected $dates = [
        'created_at',
        'updated_at',
        'period_start_date',
        'period_end_date',
        'previous_period_start_date',
        'previous_period_end_date'
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

}
