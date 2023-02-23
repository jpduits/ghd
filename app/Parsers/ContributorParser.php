<?php

namespace App\Parsers;

use Github\Client;
use App\Models\User;
use App\Models\Repository;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class ContributorParser extends BaseParser
{

    private UserParser $userParser;

    public function __construct(Client $client, Output $output, UserParser $userParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }

    public function getContributors(Repository $repository): void
    {

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/contributors?per_page=100&state=all';
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->writeToTerminal('Get contributors for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github+json']);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $contributors = ResponseMediator::getContent($response);

            // save the commits
            $this->writeToTerminal('Saving contributors for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($contributors as $contributor) {

                // check contributor is type user
                if ($contributor['type'] !== 'User') {
                    continue;
                }

                $this->userParser->userExistsOrCreate($contributor);


            }

            // no next page, break from while
            if (!isset($links['next'])) {
                //if (true) { //debug
                break;
            }

            // else get next page
            $page++;
            $uri = $links['next']['link'];

        }

        return;

    }

}
