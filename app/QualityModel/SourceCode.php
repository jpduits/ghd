<?php

namespace App\QualityModel;

use Carbon\Carbon;
use App\Models\Commit;
use App\Traits\Terminal;
use App\Models\Repository;
use App\QualityModel\Metrics\SIG\SigRanking;
use Symfony\Component\Console\Output\Output;
use App\QualityModel\Metrics\SIG\VolumeMetric;
use App\QualityModel\Metrics\SIG\DuplicationMetric;
use App\QualityModel\Metrics\SIG\CC_UnitSizeMetric;
use App\QualityModel\Metrics\Community\CommentMetric;

class SourceCode
{
    use Terminal;

    private VolumeMetric $volumeMetric;
    private CC_UnitSizeMetric $complexity_UnitSizeMetric;
    private DuplicationMetric $duplicationMetric;
    private Output $output;

    protected bool $verbose = false;
    private SigRanking $sigRanking;
    private CommentMetric $commentMetric;

    private string $checkoutDir;

    public function __construct(Output $output, VolumeMetric $volumeMetric, CC_UnitSizeMetric $complexity_UnitSizeMetric,
                                DuplicationMetric $duplicationMetric, SigRanking $sigRanking, CommentMetric $commentMetric)
    {
        $this->volumeMetric = $volumeMetric;
        $this->complexity_UnitSizeMetric = $complexity_UnitSizeMetric;
        $this->duplicationMetric = $duplicationMetric;
        $this->commentMetric = $commentMetric;

        $this->checkoutDir = env('GITHUB_TMP_CHECKOUT_DIR');

        $this->output = $output;
        $this->sigRanking = $sigRanking;
    }

    public function setVerbose(bool $verbose) : void
    {
        $this->verbose = $verbose;
    }


    public function get(Repository $repository, Carbon $startDate, int $periodInterval = 26, Carbon $endDate = null): array
    {
        // check of repository al is gecloned in temp directory
        if (!file_exists($this->checkoutDir.'/'.$repository->name)) {
            $fullName = $repository->full_name;

            $this->output->writeln('Cloning repository '.$fullName.' in temporary directory...');
            if (!$this->cloneRepository($fullName)) {
                $this->output->writeln('Error cloning repository '.$fullName.' to temporary directory');
                exit(1);
            }

        }

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

    public function calculate(Repository $repository, Carbon $startDate, int $periodInterval = null) : array
    {
        $results = [];

        $periodStartDate = $startDate->copy(); // start period Pi
        $periodEndDate = $startDate->copy()->addWeeks($periodInterval); // end period Pi

        $commitHash = $this->getLatestCommitHash($repository, $periodStartDate, $periodEndDate);
        if ($commitHash) {
            $this->writeToTerminal('Commit hash: ' . $commitHash);
        }
        else {
            $this->writeToTerminal('No commit hash found', 'error');
        }
        // checkout commit
        if ($commitHash) {

            $this->checkoutCommit($repository, $commitHash);
            // TODO: what to do if there is no commit hash in this period?


            if (file_exists($this->checkoutDir . '/' . $repository->name)) {

                // calculate volume metrics
                $volume = $this->volumeMetric->calculate($repository);
                $loc = $volume['total_loc'];

                // calculate cyclomatic complexity and unit size
                $complexity_UnitSize = $this->complexity_UnitSizeMetric->calculate($repository, $loc);

                // calculate duplication
                $duplication = $this->duplicationMetric->calculate($repository, $loc);

                $comments = $this->commentMetric->get($repository);

                $results = array_merge($volume, $complexity_UnitSize, $duplication, $comments);

            }
            else {
                $this->writeToTerminal('Checkout directory not found!', 'error');
            }

            // map to system level score and merge
            $results = array_merge($results, $this->sigRanking->calculate($results));

            return array_merge(
                $results, [
                'period_start_date' => $periodStartDate->format('Y-m-d'),
                'period_end_date' => $periodEndDate->format('Y-m-d'),
                'checkout_sha' => $commitHash
            ]);

        }

        return [];

    }

    private function checkoutCommit(Repository $repository, string $commitHash) : bool
    {

        if (file_exists($this->checkoutDir.'/'.$repository->name)) {

            $this->writeToTerminal('Checking out commit ' . $commitHash);
            exec('cd ' . $this->checkoutDir . '/' . $repository->name . ' && git checkout ' . $commitHash, $output);

            if ($this->verbose) {
                foreach ($output as $line) {
                    $this->writeToTerminal($line);
                }
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
        else {
            // no commit in this period, search for latest commit
            $commit = $repository->commits()
                                 ->where('created_at', '<=', $startDate)
                                 ->orderBy('created_at', 'desc')
                                 ->first();

            if ($commit instanceof Commit) {
                $this->writeToTerminal('No commit in this period, get latest previous commit', 'error');
                return $commit->sha;
            }


        }

        $this->writeToTerminal('No commit found!', 'error');
        return null;
    }

    private function cloneRepository(string $fullName) : bool
    {
        $output = [];
        exec('cd '.$this->checkoutDir.' && git clone https://github.com/'.$fullName.'.git', $output);

        /*        if ($this->verbose) {
                    foreach ($output as $line) {
                        $this->line($line);
                    }
                }*/
        return true;
    }

}
