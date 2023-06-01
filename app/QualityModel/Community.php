<?php

namespace App\QualityModel;

use Carbon\Carbon;
use App\Traits\Terminal;
use App\Models\Repository;
use Symfony\Component\Console\Output\Output;
use App\QualityModel\Metrics\Community\GithubMeta;
use App\QualityModel\Metrics\Yamashita\MagnetMetric;
use App\QualityModel\Metrics\Yamashita\StickyMetric;

class Community
{
    use Terminal;


    private MagnetMetric $magnetMetric;
    private StickyMetric $stickyMetric;
    private GithubMeta $githubMeta;
    private Output $output;

    public function __construct(Output $output, MagnetMetric $magnetMetric, StickyMetric $stickyMetric, GithubMeta $githubMeta)
    {
        $this->magnetMetric = $magnetMetric;
        $this->stickyMetric = $stickyMetric;
        $this->githubMeta = $githubMeta;
        $this->output = $output;
    }

    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
        $measurements = []; // loop results

        while (true) {

            $sticky = $this->stickyMetric->calculate($repository, $startDate, $periodInterval);
            $magnet = $this->magnetMetric->calculate($repository, $startDate, $periodInterval);
            $gitHub = $this->githubMeta->calculate($repository, $startDate, $periodInterval);

            $measurements[] = array_merge($sticky, $magnet, $gitHub); // dates are the same, so merge is ok

            if ($endDate) { // when endDate is set, loop until endDate is reached

                $startDate->addWeeks($periodInterval); // add interval to start date
                if (($startDate->gt($endDate)) || ($startDate->gt(Carbon::now()))) {
                    break;
                }
            }
            else {
                break;
            }

        }

        return $measurements;
    }


}
