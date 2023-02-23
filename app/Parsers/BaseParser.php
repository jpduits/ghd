<?php

namespace App\Parsers;

use Github\Client;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class BaseParser
{
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

    /**
     * @param string $message
     * @param string|null $style
     * @return void
     */
    public function writeToTerminal(string $message, string $style = null) : void
    {
        $styles = [
            'info-green' => '<options=bold;fg=green>',
            'info-yellow' => '<options=bold;fg=yellow>',
            'info-red' => '<options=bold;fg=red>',
            'info' => '<options=bold>',
            'comment' => '<fg=black;bg=blue>',
            'question' => '<fg=black;bg=green>',
            'error' => '<fg=black;bg=red>',
            'warning' => '<fg=black;bg=yellow>',
        ];

        if (array_key_exists($style, $styles)) {
            $message = $styles[$style] . $message . '</>';
        }
        $this->output->writeLn($message, OutputInterface::OUTPUT_NORMAL);
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
