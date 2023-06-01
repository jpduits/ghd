<?php

namespace App\QualityModel\Metrics;

use App\Traits\Terminal;
use Symfony\Component\Console\Output\Output;

class BaseMetric
{
    use Terminal;

    protected string $checkoutDir;


    /**
     * @var Output $output
     */
    protected Output $output;
    /**
     * @var false
     */
    protected bool $verbose = false;

    /**
     * @param Output $output
     */
    public function __construct(Output $output, $verbose = false)
    {
        $this->output = $output;
        $this->verbose = $verbose;

        $this->checkoutDir = env('GITHUB_TMP_CHECKOUT_DIR');
    }

    public function setVerbose(bool $verbose) : void
    {
        $this->verbose = $verbose;
    }

}
