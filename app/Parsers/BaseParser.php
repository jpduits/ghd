<?php

namespace App\Parsers;

use Github\Client;
use App\Traits\Terminal;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class BaseParser
{
    use Terminal;

    /**
     * @var Client $client
     */
    protected Client $client;

    /**
     * @var Output $output
     */
    protected Output $output;


    /**
     * @param Client $client
     * @param Output $output
     */
    public function __construct(Client $client, Output $output)
    {
        $this->client = $client;
        $this->output = $output;
    }

    public function checkRemainingRequests(array $headers, bool $activateSleep = true) : int
    {
        if (isset($headers['X-RateLimit-Remaining'][0])) {
            $remainingRequests = $headers['X-RateLimit-Remaining'][0];
            $maxRequests = $headers['X-RateLimit-Limit'][0];

            $this->writeToTerminal('Remaining requests: '.$remainingRequests.'/'.$maxRequests, 'info-green');

            if ($remainingRequests <= 1  && $activateSleep) {
                $this->writeToTerminal('Waiting for 1 hour...', 'info');

                $animatedCharacter = '>';
                $i = $y = 1;

                while ($i < 61) {
                    $this->output->write("\x0D");
                    $this->output->write($animatedCharacter . str_repeat('>', $y) . '>');
                    if (($i%5 == 0) && ($i != 1)) {
                        $this->output->writeln(' ' . $i . ' minutes passed');
                        $y=0;
                    }
                    sleep(60);
                    $i++;
                    $y++;
                }
            }

        }

        return $remainingRequests;

    }

}
