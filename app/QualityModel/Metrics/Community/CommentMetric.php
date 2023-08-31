<?php

namespace App\QualityModel\Metrics\Community;

use App\Models\Repository;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class CommentMetric extends BaseMetric
{
    const COMMENTS_PARSER = './scripts/analyzers/comments/get_comments_project.sh';

    const CLASSIFY_COMMENTS = 'python3 ./scripts/analyzers/comments/classify_comments.py';


    public function __construct(Output $output)
    {
        parent::__construct($output);
    }

    public function get(Repository $repository) : array
    {

        $results = [];
        $totalComments = 0;

        // check repository is cloned
        if (file_exists($this->checkoutDir.'/'.$repository->name)) {

            // results are stored in processed/comments_COMMITHASH.csv
            $command = self::COMMENTS_PARSER . ' ' . $this->checkoutDir.'/'.$repository->name;

            // get comments from current commit
            $this->writeToTerminal('Executing command: '.$command);
            exec($command, $output);

            // get filename from parsed output, last item from output array
            $filename = './'.$output[count($output)-1];
            if (file_exists($filename)) {

                // classify comments
                $command = self::CLASSIFY_COMMENTS . ' ' . $filename;
                exec($command, $classifications);

                foreach ($classifications as $classification) {
                    $classification = explode(',', $classification);
                    $totalComments += (int) trim($classification[1]) ?? 0;
                    $results['comments_'.strtolower($classification[0])] = $classification[1];
                }

            }

        }


        $results['comments_total'] = $totalComments;
        $results['comments_relevant_percentage'] = ($totalComments && $results['comments_relevant']) ? round(($results['comments_relevant'] / $totalComments) * 100) : 0;

        return $results;
    }

}
