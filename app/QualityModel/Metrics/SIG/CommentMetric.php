<?php

namespace App\QualityModel\Metrics\SIG;

use App\Models\Repository;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class CommentMetric extends BaseMetric
{
    const COMMENTS_PARSER = './scripts/analyzers/comments/get_comments_project.sh';

    const CLASSIFY_COMMENTS = 'python3 ./scripts/analyzers/comments/classify_comments.py';

    const COMMENTS_RANKING = [
        ['min' => 35, 'max' => 100, 'min_relevant_comments' => 88, 'ranking' => '++', 'value' => 5],
        ['min' => 17, 'max' => 35, 'min_relevant_comments' => 0, 'ranking' => '+', 'value' => 4],
        ['min' => 7, 'max' => 17, 'min_relevant_comments' => 0, 'ranking' => 'o', 'value' => 3],
        ['min' => 2, 'max' => 7, 'min_relevant_comments' => 0, 'ranking' => '-', 'value' => 2],
        ['min' => 0, 'max' => 2, 'min_relevant_comments' => 0, 'ranking' => '--', 'value' => 1],
    ];


    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function calculate(Repository $repository, int $totalLines) : array
    {

        $results = [];
        $totalComments = 0;
        $commentsPercentage = 0;

        // check repository is cloned
        if (file_exists($this->checkoutDir.'/'.$repository->name)) {

            // results are stored in processed/comments_COMMITHASH.csv
            // (test) files are filtered in script
            $command = self::COMMENTS_PARSER . ' ' . $this->checkoutDir.'/'.$repository->name;

            // get comments from current commit
            $this->writeToTerminal('Executing command: '.$command);
            exec($command, $output);

            // get filename from parsed output, last item from output array
            $filename = './'.$output[count($output)-1];
            $this->writeToTerminal('Parsed output saved in: '.$filename);
            if (file_exists($filename)) {
                // classify comments
                $command = self::CLASSIFY_COMMENTS . ' ' . $filename;
                $this->writeToTerminal('Executing command: '.$command);
                exec($command, $classifications);

                foreach ($classifications as $classification) {
                    $classification = explode(',', $classification);
                    if (!str_contains($classification[0], 'LOC')) {
                        // counts comment blocks, not comment lines
                       // echo "===========>".$totalComments.' + '.$classification[1];
                        $totalComments += (int)trim($classification[1]) ?? 0;
                    }
                    $results['comments_'.strtolower($classification[0])] = $classification[1] ?? 0;
                }

            }

            // calculate percentage comments.
            if (isset($results['comments_loc'])) {
                $commentsPercentage = round(($results['comments_loc'] / $totalLines) * 100);
            }


        }

        $results['comments_percentage'] = $commentsPercentage;
        $results['comments_total'] = $totalComments;

        $commentsRelevantPercentage = ($totalComments && isset($results['comments_relevant'])) ? round(($results['comments_relevant'] / $totalComments) * 100) : 0;
        $results['comments_relevant_percentage'] = $commentsRelevantPercentage; // not loc but comment blocks

        $ranking = $this->getCommentsRanking($commentsPercentage, $commentsRelevantPercentage);

        $results['sig_comments_ranking'] = $ranking['ranking'] ?? 'o';
        $results['sig_comments_ranking_numeric'] = $ranking['value'] ?? 3;

        return $results;
    }

    private function getCommentsRanking(int $commentsPercentage, int $commentsRelevantPercentage) : ?array
    {
        foreach (self::COMMENTS_RANKING as $ranking) {
            if ($commentsPercentage >= $ranking['min'] && $commentsPercentage < $ranking['max'] && $commentsRelevantPercentage >= $ranking['min_relevant_comments']) {
                return $ranking;
            }
        }
        return null;
    }

}
