<?php

namespace App\Metrics;

use App\Traits\Terminal;
use Symfony\Component\Console\Output\Output;

class BaseMetric
{
    use Terminal;

    /**
     * @var Output $output
     */
    protected Output $output;

    /**
     * @param Output $output
     */
    public function __construct(Output $output)
    {
        $this->output = $output;
    }

}
