<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectState extends Model
{

    protected $table = 'project_states';

    protected $appends = [
        // 'repository_full_name'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'period_start_date',
        'period_end_date',
        'previous_period_start_date',
        'previous_period_end_date'
    ];

    // transform date format
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'period_start_date' => 'datetime:Y-m-d',
        'period_end_date' => 'datetime:Y-m-d',
        'previous_period_start_date' => 'datetime:Y-m-d',
        'previous_period_end_date' => 'datetime:Y-m-d'
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

    public function getrepositoryFullNameAttribute()
    {
        return $this->repository->value('full_name');
    }

}
