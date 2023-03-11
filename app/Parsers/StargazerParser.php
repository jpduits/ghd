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
                $starredAtDate = $stargazer['starred_at'];
                $starredAt = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $starredAtDate)->toDateTimeString();
                $user = $this->userParser->userExistsOrCreate($stargazer['user']);

                $stargazerRecord = Stargazer::where('user_id', '=', $user->id)
                                            ->where('repository_id', '=', $repository->id)
                                            ->where('starred_at', '=', $starredAt)
                                            ->first();
                if (!$stargazerRecord instanceof Stargazer) {
                    // Commit does not exist, create
                    $stargazerRecord = new Stargazer();
                    $stargazerRecord->user_id = $user->id;
                    $stargazerRecord->repository_id = $repository->id;
                    $stargazerRecord->starred_at = $starredAt;

                    if ($stargazerRecord->save()) {
                        $this->writeToTerminal('Startgazer user: '.$stargazer['user']['id'].' saved ('.$stargazerCounter.').');
                        $stargazerCounter++;
                    }
                    else {
                        $this->writeToTerminal('Error saving startgazer user: '.$stargazer['user']['id'].' ('.$stargazerCounter.').', 'info-red');
                        print_r($stargazer);
                        print_r($user);
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
