<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Stargazer;
use App\Models\Repository;
use TiagoHillebrandt\ParseLinkHeader;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class StargazerParser extends BaseParser
{

    private UserParser $userParser;

    public function __construct(Client $client, Output $output, UserParser $userParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }


    public function getStargazers(Repository $repository)
    {
        $stargazerCounter = 0;

        $page = $this->getFailSave($repository, 'stargazers'); // default = 1
        $uri = 'repos/'.$repository->full_name.'/stargazers?per_page=100&page='.$page;
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->writeToTerminal('Get stargazers for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github.star+json']);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $stargazers = ResponseMediator::getContent($response);

            // save the commits
            $this->writeToTerminal('Saving stargazers for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($stargazers as $stargazer) {
                // first check stargazer exists
                $stargazerRecord = Stargazer::where('user_id', '=', $stargazer['user']['id'])->where('repository_id', '=', $repository->id)->first();
                if (!$stargazerRecord instanceof Stargazer) {
                    // Commit does not exist, create
                    $stargazerRecord = new Stargazer();
                    $user = $this->userParser->userExistsOrCreate($stargazer['user']);
                    $stargazerRecord->user_id = $user->id;
                    $stargazerRecord->repository_id = $repository->id;
                    $starredAtDate = $stargazer['starred_at'];
                    $stargazerRecord->starred_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $starredAtDate)->toDateTimeString();

                    if ($stargazerRecord->save()) {
                        $this->writeToTerminal('Startgazer user: '.$stargazer['user']['id'].' saved ('.$stargazerCounter.').');
                        $stargazerCounter++;
                    }
                }
                else {
                    $this->writeToTerminal('Startgazer user: '.$stargazer['user']['id'].' already exists, skipping.');
                }

            }

            // no next page, break from while
            if (!isset($links['next'])) {
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

            // save fail save
            $this->setFailSave($repository, 'stargazers', $page);

        }

        return $stargazerCounter;
    }

}
