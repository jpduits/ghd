<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Commit;
use App\Models\Repository;
use TiagoHillebrandt\ParseLinkHeader;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class CommitParser extends BaseParser
{

    private UserParser $userParser;

    public function __construct(Client $client, Output $output, UserParser $userParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }

    public function getCommits(Repository $repository, int $pullRequestNumber = null)
    {
        $commitCounter = 0;

        $page = 1;
        $lastPage = 1;

        $suffix = '';
        if ($pullRequestNumber) {
            $uri = 'repos/' . $repository->full_name . '/pulls/'.$pullRequestNumber.'/commits?per_page=100';
            $suffix = ' (for not merged PullRequest '.$pullRequestNumber.')';
        }
        else {
            $uri = 'repos/' . $repository->full_name . '/commits?per_page=100';
        }

        while (true) {

            $this->writeToTerminal('Get commits'.$suffix.' for '.$repository->full_name.' page '.$page);

            $response = $this->client->getHttpClient()->get($uri);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? 1;
            }

            $commits = ResponseMediator::getContent($response);

            // save the commits
            $this->writeToTerminal('Saving commits'.$suffix.' for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($commits as $commit) {

                // first check commit exists
                $commitRecord = Commit::where('sha', '=', $commit['sha'])->first();
                if (!$commitRecord instanceof Commit) {
                    // Commit does not exist, create
                    $commitRecord = new Commit();
                    $commitRecord->sha = $commit['sha'];

                    $commitRecord->repository_id = $repository->id;

                    $commitDate = $commit['commit']['author']['date'];
                    $commitRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $commitDate)->toDateTimeString();

                    if (isset($commit['author']['id'])) {
                        $authorId = $commit['author']['id'];
                        $author = $this->userParser->userExistsOrCreate($authorId);
                    }
                    else {
                        $author = $this->userParser->userExistsOrCreate(0); // user deleted
                    }

                    if (isset($commit['committer']['id'])) {
                        $committerId = $commit['committer']['id'];
                        $committer = $this->userParser->userExistsOrCreate($committerId);
                    }
                    else {
                        $committer = $this->userParser->userExistsOrCreate(0); // user deleted
                    }

                    $commitRecord->author_id = $author->id ?? null;
                    $commitRecord->committer_id = $committer->id ?? null;

                    $commitRecord->message = $commit['commit']['message'] ?? '';
                    $commitRecord->node_id = $commit['node_id'];
                    $commitRecord->html_url = $commit['html_url'];
                    $commitRecord->save();
                    $commitCounter++;
                }
                else {
                    $this->writeToTerminal('Commit: '.$commit['sha'].' already exists, skipping.', 'warning');
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

        $this->writeToTerminal(sprintf('%s commits saved for repository (%s)', $commitCounter, $repository->full_name));
    }


}
