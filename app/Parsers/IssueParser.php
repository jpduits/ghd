<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Repository;
use TiagoHillebrandt\ParseLinkHeader;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class IssueParser extends BaseParser
{
    const SINCE = '2023-03-14T11:45:53Z'; // label fix

    private UserParser $userParser;

    public function __construct(Client $client, Output $output, UserParser $userParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }


    public function getIssues(Repository $repository)
    {

        $issuesCounter = 0;

        $page = $this->getFailSave($repository, 'issues'); // default = 1
        $uri = 'repos/'.$repository->full_name.'/issues?per_page=100&state=all&page='.$page;
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->writeToTerminal('Get issues for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github.full+json']);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $issues = ResponseMediator::getContent($response);

            $this->writeToTerminal('Saving issues for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($issues as $issue) {
                // first check issue exists
                $issueRecord = Issue::where('github_id', '=', $issue['id'])->first();
                if (!$issueRecord instanceof Issue) { // else attach labels
                    break;
                    // Commit does not exist, create
                    $issueRecord = new Issue();
                    $issueRecord->github_id = $issue['id'];
                    $user = $this->userParser->userExistsOrCreate($issue['user']);
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

                    $issueRecord->title = substr($issue['title'], 0, 255);
                    $issueRecord->body = $issue['body'] ?? '';
                    $issueRecord->state = $issue['state'];
                    $issueRecord->number = $issue['number'] ?? null;
                    $issueRecord->comments = $issue['comments'] ?? 0;
                    $issueRecord->url = $issue['url'];
                    $issueRecord->html_url = $issue['html_url'];

                    if ($issueRecord->save()) {
                        $this->writeToTerminal('Issue: '.$issue['id'] . ' saved ('.$issuesCounter.').');
                        $issuesCounter++;
                    }

                }
                else {

                    // Issue exists, check if labels are attached, workaround
                    if ($issue['labels'] !== []) {
                        // check label exists
                        $labelIds = [];

                        foreach ($issue['labels'] as $label) {

                            $labelRecord = Label::where('github_id', '=', $label['id'])->first();
                            if (!$labelRecord instanceof Label) {
                                // create label
                                $labelRecord = new Label();
                                $labelRecord->github_id = $label['id'];
                                $labelRecord->name = $label['name'];
                                $labelRecord->description = $label['description'] ?? '';
                                $labelRecord->save();

                                $this->writeToTerminal('Label '.$label['name'].' added.', 'info-warning');
                            }
                            $labelIds[] = $labelRecord->id;

                        }

                        // attach to issue
                        if (count($labelIds) > 0) {
                            $issueRecord->labels()->attach($labelIds);
                        }

                    }

                    //$this->writeToTerminal('Issue: '.$issue['id'].' already exists, skipping.', 'info-warning');
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
            $this->setFailSave($repository, 'issues', $page);

        }

        return $issuesCounter;

    }


}
