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

    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
        // if endDate is null, set it to now
        // default interval is 26 means a period is a half year (26 weeks)

        $measurements = []; // loop results

        // calculate sticky value for a range of periods
        while (true) {

            return;
        }
    }

}
