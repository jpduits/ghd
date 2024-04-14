<?php

namespace App\QualityModel;

use Carbon\Carbon;
use App\Models\Commit;
use App\Traits\Terminal;
use App\Models\Repository;
use App\QualityModel\Metrics\SIG\SigRanking;
use Symfony\Component\Console\Output\Output;
use App\QualityModel\Metrics\SIG\VolumeMetric;
use App\QualityModel\Metrics\SIG\CommentMetric;
use App\QualityModel\Metrics\SIG\DuplicationMetric;
use App\QualityModel\Metrics\SIG\CC_UnitSizeMetric;

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
                $periodEndDate = $startDate->copy()->addWeeks($periodInterval);
                // Start datum groter dan einddatum
                // Start datum groter dan vandaag
                // Periode eind ligt voorbij einddatum
                if ( ($startDate->gt($endDate)) || ($startDate->gt(Carbon::now())) || ($periodEndDate->gt($endDate)) ) {
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

        $commitSha = $this->getLatestCommitHash($repository, $periodStartDate, $periodEndDate);
        if ($commitSha) {
            $this->writeToTerminal('Commit SHA: ' . $commitSha);
        }
        else {
            $this->writeToTerminal('No commit SHA found', 'error');
        }
        // checkout commit
        if ($commitSha) {

            $this->checkoutCommit($repository, $commitSha);
            // TODO: what to do if there is no commit hash in this period?


            if (file_exists($this->checkoutDir . '/' . $repository->name)) {

                // calculate volume metrics
                $volume = $this->volumeMetric->calculate($repository); // oldskool LOC
                $loc = $volume['total_loc']; // Loc
                $totalLines  = $volume['total_lines'];

                // calculate cyclomatic complexity and unit size
                $complexity_UnitSize = $this->complexity_UnitSizeMetric->calculate($repository, $loc);

                // calculate duplication
                $duplication = $this->duplicationMetric->calculate($repository, $loc);

                $comments = $this->commentMetric->calculate($repository, $totalLines);

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
                'checkout_sha' => $commitSha
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
/*        $commit = $repository->commits()
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


        }*/

        // first checkout the default branch (if switched to sha)
        exec('cd ' . $this->checkoutDir . '/' . $repository->name . ' && git checkout ' . $repository->default_branch, $outputD);
        if ($this->verbose) {
            foreach ($outputD as $line) {
                $this->writeToTerminal($line);
            }
        }

        $output = [];
        // get latest 10 commit SHA's from specific date
        exec('cd ' . $this->checkoutDir . '/' . $repository->name . ' && TZ="Europa/Amsterdam" git rev-list -n 10 --before="'.$endDate->format('Y-m-d').'" HEAD', $output);
        $n = 0;

        while (true && ($n < 10)) {

            $sha = null;

            if ((is_array($output)) && (count($output) > 0) && (strlen($output[$n]) == 40)) {


                $sha = $output[$n];
                $this->writeToTerminal('Last commit SHA before ' . $endDate->format('Y-m-d') . ' found: ' . $sha, 'error');
                // check database exists

                if ($repository->commits()->where('sha', '=', $sha)->count() == 1) {
                    $this->writeToTerminal('SHA: '.$sha.' found in database!');
                    break;
                }
                else {
                    // not found in db, loop again
                    $this->writeToTerminal('SHA: '.$sha.' not found in database', 'error');
                    $n++;
                }

            }
            else {
                break; // no hash found
            }

        }

        if ($sha) {
            $this->writeToTerminal('Valid commit SHA before ' . $endDate->format('Y-m-d') . ' found: ' . $sha);
            return $sha;
        }

        $this->writeToTerminal('No commit SHA found before '.$endDate->format('Y-m-d'), 'error');
        return null;
    }

    private function cloneRepository(string $fullName) : bool
    {
        $output = [];
        exec('cd '.$this->checkoutDir.' && git clone https://github.com/'.$fullName.'.git', $output);
        return true;
    }

}
