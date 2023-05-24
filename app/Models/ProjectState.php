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

    protected $fillable = [
        'run_uuid',
        'repository_id',
        'period_start_date',
        'period_end_date',
        'previous_period_start_date',
        'previous_period_end_date',
        'interval_weeks',
        'sticky_metric_score',
        'magnet_metric_score',
        'developers_total',
        'developers_new_current_period',
        'developers_current_period',
        'developers_with_contributions_previous_period',
        'developers_with_contributions_previous_and_current_period',
        'issues_count_current_period',
        'issues_count_total',
        'stargazers_count_current_period',
        'stargazers_count_total',
        'pull_requests_count_current_period',
        'pull_requests_count_total',
        'forks_count_current_period',
        'forks_count_total',
        'checkout_sha',
        'volume',
        'complexity',
        'duplication',
        'unit_size',
        'maintainability_index',
    ];


    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

}
