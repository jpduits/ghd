<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Issue;
use App\Models\Repository;
use TiagoHillebrandt\ParseLinkHeader;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class IssueParser extends BaseParser
{

    private UserParser $userParser;

    public function __construct(Client $client, Output $output, UserParser $userParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }


    public function getIssues(Repository $repository)
    {

        $issuesCounter = 0;

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/issues?per_page=100&state=all';
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->writeToTerminal('Get issues for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github+json']);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $issues = ResponseMediator::getContent($response);

            // save the commits
            $this->writeToTerminal('Saving issues for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($issues as $issue) {
                // first check stargazer exists
                $issueRecord = Issue::find($issue['id']);
                if (!$issueRecord instanceof Issue) {
                    // Commit does not exist, create
                    $issueRecord = new Issue();
                    $issueRecord->id = $issue['id'];
                    $user = $this->userParser->userExistsOrCreate($issue['user']['id']);
                    $issueRecord->user_id = $user->id;
                    $issueRecord->repository_id = $repository->id;
                    $closedAtDate = $issue['closed_at'];
                    if ($closedAtDate !== null) {
                        $issueRecord->closed_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $closedAtDate)->toDateTimeString();
                    }

                    $createdAtDate = $issue['created_at'];
                    if ($createdAtDate !== null) {
                        $issueRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
                    }

                    $updatedAtDate = $issue['updated_at'];
                    if ($updatedAtDate !== null) {
                        $issueRecord->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
                    }

                    $issueRecord->title = $issue['title'];
                    $issueRecord->description = $issue['body'] ?? '';
                    $issueRecord->state = $issue['state'];
                    $issueRecord->node_id = $issue['node_id'];
                    $issueRecord->number = $issue['number'] ?? null;
                    $issueRecord->comments = $issue['comments'] ?? 0;
                    $issueRecord->html_url = $issue['html_url'];

                    $issueRecord->save();
                    $issuesCounter++;
                }
                else {
                    $this->writeToTerminal('Issue: '.$issue['id'].' already exists, skipping.');
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

        }

        return $issuesCounter;

    }

}
