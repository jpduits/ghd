<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectState extends Model
{

    protected $table = 'project_states';

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
        'developers_new',
        'developers_current',
        'developers_with_contributions_previous_period',
        'developers_with_contributions_previous_and_current_period',
        'issues_count_new',
        'issues_count_total',
        'stargazers_count_new',
        'stargazers_count_total',
        'pull_requests_count_new',
        'pull_requests_count_total',
        'forks_count_new',
        'forks_count_total'
    ];


    public function repository()
    {
        return $this->belongsTo(Repository::class, 'repository_id', 'id');
    }

}
