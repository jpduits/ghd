<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Fork;
use App\Models\Repository;
use TiagoHillebrandt\ParseLinkHeader;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class ForkParser extends BaseParser
{

    private UserParser $userParser;

    public function __construct(Client $client, Output $output, UserParser $userParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }


    public function getForks(Repository $repository)
    {
        $forkCounter = 0;

        $page = $this->getFailSave($repository, 'forks'); // default = 1
        $uri = 'repos/'.$repository->full_name.'/forks?per_page=100&page='.$page;
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->writeToTerminal('Get forks for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github.star+json']);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $forks = ResponseMediator::getContent($response);

            // save the commits
            $this->writeToTerminal('Saving forks for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($forks as $fork) {
                // first check fork exists
                $forkRecord = Fork::where('github_id', '=', $fork['id'])->where('repository_id', '=', $repository->id)->first();
                if (!$forkRecord instanceof Fork) {

                    $forkRecord = new Fork();
                    $owner = $this->userParser->userExistsOrCreate($fork['owner']);
                    $forkRecord->github_id = $fork['id'];
                    $forkRecord->owner_id = $owner->id;
                    $forkRecord->full_name = $fork['full_name'];
                    $forkRecord->url = $fork['url'];
                    $forkRecord->html_url = $fork['html_url'];
                    $forkRecord->repository_id = $repository->id;
                    $createdAtDate = $fork['created_at'];
                    $forkRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
                    if ($forkRecord->save()) {
                        $this->writeToTerminal('Fork: ' . $fork['full_name'] . ' saved ('.$forkCounter.').');
                        $forkCounter++;
                    }

                }
                else {
                    $this->writeToTerminal('Fork: '.$fork['full_name'].' already exists, skipping.', 'info-red');
                }

            }

            // no next page, break from while
            if (!isset($links['next'])) {
                //if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

            // save fail save
            $this->setFailSave($repository, 'forks', $page);

        }

        return $forkCounter;
    }

}
