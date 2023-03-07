<?php

namespace App\Parsers;

use Github\Client;
use App\Traits\Terminal;
use App\Models\FailSave;
use App\Models\Repository;
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

    public function setFailSave(Repository $repository, string $parser, int $page)
    {
        $failSave = FailSave::where('repository_id', '=', $repository->id)->where('finished', '=', false)->firstOrCreate();
        $failSave->repository_id = $repository->id;
        $failSave->parser = $parser;
        $failSave->page = $page;
        $failSave->save();
    }


    public function getFailSave(Repository $repository, string $parser)
    {
        $failSave = FailSave::where('repository_id', '=', $repository->id)->where('parser', '=', $parser)->first();
        if ($failSave instanceof FailSave) {
            $this->writeToTerminal('Found fail save for '.$parser.' on page '.$failSave->page, 'info-green');
            return $failSave->page;
        }
        return 1;
    }

}
