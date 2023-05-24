<?php

namespace App\Metrics;

use Carbon\Carbon;
use App\Models\Commit;
use App\Models\Repository;
use Symfony\Component\Console\Output\Output;

class QualityMetric extends BaseMetric
{
    private string $checkoutDir;

    public function __construct(Output $output)
    {
        parent::__construct($output);

        $this->checkoutDir = env('GITHUB_TMP_CHECKOUT_DIR');
    }

    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
        // clone repo if not exists?


        $measurements = []; // loop results

        while (true) {

            $measurements[] = $this->calculate($repository, $startDate, $periodInterval);
            //$this->writeToTerminal('Sticky value: ' . $sticky);

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


    private function calculate(Repository $repository, Carbon $startDate, int $periodInterval = null) : array
    {
        $periodStartDate = $startDate->copy(); // start period Pi
        $periodEndDate = $startDate->copy()->addWeeks($periodInterval); // end period Pi

        $commitHash = $this->getLatestCommitHash($repository, $periodStartDate, $periodEndDate);
        $this->writeToTerminal('Commit hash: ' . $commitHash);
        // checkout commit
        if ($commitHash) {
            $this->checkoutCommit($repository, $commitHash);
            // TODO: what to do if there is no commit hash in this period?

            // do quality checks


        }

        return [
            'period_start_date' => $periodStartDate->format('Y-m-d'),
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'checkout_sha' => $commitHash
        ];
    }

    private function checkoutCommit(Repository $repository, string $commitHash) : bool
    {

        if (file_exists($this->checkoutDir.'/'.$repository->name)) {
            $this->writeToTerminal('Checking out commit ' . $commitHash);
            $output = [];
            exec('cd ' . $this->checkoutDir . '/' . $repository->name . ' && git checkout ' . $commitHash, $output);
            foreach ($output as $line) {
                $this->writeToTerminal($line);
            }
            return true;
        }
        return false;
    }



    private function getLatestCommitHash(Repository $repository, Carbon $startDate, Carbon|null $endDate) : ?string
    {
        $commit = $repository->commits()
                             ->where('created_at', '>=', $startDate)
                             ->where('created_at', '<=', $endDate ?? Carbon::now())
                             ->orderBy('created_at', 'desc')
                             ->first();
        if ($commit instanceof Commit) {
            return $commit->sha;
        }
        return null;
    }

    private function getVolume(Repository $repository) : int
    {
        $output = [];
        exec('cd ' . $this->checkoutDir . ' && pmd check ' . $repository->name, $output);
        foreach ($output as $line) {
            $this->writeToTerminal($line);
        }
        return 0;
    }

}
