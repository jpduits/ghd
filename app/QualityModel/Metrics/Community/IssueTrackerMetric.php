<?php

namespace App\QualityModel\Metrics\Community;

use Carbon\Carbon;
use App\Models\Repository;
use Symfony\Component\Console\Output\Output;

class IssueTrackerMetric
{

    public function __construct(Output $output)
    {
        parent::__construct($output);
    }



}
