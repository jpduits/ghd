<?php

namespace App\QualityModel;

use Carbon\Carbon;
use App\Traits\Terminal;
use App\Models\Repository;
use Symfony\Component\Console\Output\Output;
use App\QualityModel\Metrics\Community\GithubMeta;
use App\QualityModel\Metrics\Community\IssueMetric;
use App\QualityModel\Metrics\Yamashita\MagnetMetric;
use App\QualityModel\Metrics\Yamashita\StickyMetric;
use App\QualityModel\Metrics\Yamashita\OosCategoryMetric;

class Community
{
    use Terminal;

    private MagnetMetric $magnetMetric;
    private StickyMetric $stickyMetric;
    private GithubMeta $githubMeta;
    private Output $output;
    private OosCategoryMetric $oosCategoryMetric;
    private IssueMetric $issueMetric;

    public function __construct(Output $output, GithubMeta $githubMeta, OosCategoryMetric $oosCategoryMetric, IssueMetric $issueMetric, MagnetMetric $magnetMetric, StickyMetric $stickyMetric)
    {
        $this->githubMeta = $githubMeta;
        $this->output = $output;
        $this->oosCategoryMetric = $oosCategoryMetric;
        $this->issueMetric = $issueMetric;
    }

    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
        $measurements = []; // loop results

        while (true) {

            $issue = $this->issueMetric->calculate($repository, $startDate, $periodInterval);
            $oosCategory = $this->oosCategoryMetric->calculate($repository, $startDate, $periodInterval);
            $gitHub = $this->githubMeta->calculate($repository, $startDate, $periodInterval);

            $measurements[] = array_merge($oosCategory, $gitHub, $issue); // dates are the same, so merge is ok

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
