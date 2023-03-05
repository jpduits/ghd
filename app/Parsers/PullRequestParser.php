<?php

namespace App\Parsers;

use Exception;
use Throwable;
use Github\Client;
use Carbon\Carbon;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\PullRequest;
use TiagoHillebrandt\ParseLinkHeader;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class PullRequestParser extends BaseParser
{

    private UserParser $userParser;
    private RepositoryParser $repositoryParser;
    private CommitParser $commitParser;

    public function __construct(Client $client, Output $output, UserParser $userParser,
                                RepositoryParser $repositoryParser, CommitParser $commitParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
        $this->repositoryParser = $repositoryParser;
        $this->commitParser = $commitParser;
    }

    public function getPullRequests(Repository $repository)
    {
        // head = refers to the branch that contains the changes to be pulled or merged.
        // base = refers to the branch to which the changes will be pulled or merged.

        // The "merge_commit_sha" is the SHA (secure hash algorithm) value of the commit that merges the changes from the "head" branch into the "base" branch. It is a unique identifier for the merge commit in the Git repository.

        $pullRequestCounter = 0;

        $page = 1;
        $uri = 'repos/'.$repository->full_name.'/pulls?per_page=100&state=all';
        $httpClient = $this->client->getHttpClient();

        $lastPage = 1;

        while (true) {

            $this->writeToTerminal('Get pull requests for ' . $repository->full_name . ' page ' . $page);

            $response = $httpClient->get($uri, ['Accept' => 'application/vnd.github+json']);
            $headers = $response->getHeaders();

            $this->checkRemainingRequests($headers);

            if (isset($headers['Link'][0] )) {
                $links = (new ParseLinkHeader($headers['Link'][0]))->toArray();
                $lastPage = $links['last']['page'] ?? $lastPage;
            }

            $pullRequests = ResponseMediator::getContent($response);

            // save the commits
            $this->writeToTerminal('Saving pull requests for '.$repository->full_name.' page '.$page.'/'.$lastPage);

            foreach ($pullRequests as $pullRequest) {

                // check the pull request already exists
                $pullRequestRecord = PullRequest::where('github_id', '=', $pullRequest['id'])->first();
                if (!$pullRequestRecord instanceof PullRequest) {

                    // pull request does not exist, create
                    $pullRequestRecord = new PullRequest();
                    $pullRequestRecord->github_id = $pullRequest['id'];
                    $pullRequestRecord->number = $pullRequest['number'];
                    $pullRequestRecord->state = $pullRequest['state'];

                    $user = $this->userParser->userExistsOrCreate($pullRequest['user']);
                    $pullRequestRecord->user_id = $user->id;

                    $createdAtDate = $pullRequest['created_at'];
                    if ($createdAtDate !== null) {
                        $pullRequestRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
                    }

                    $updatedAtDate = $pullRequest['updated_at'];
                    if ($updatedAtDate !== null) {
                        $pullRequestRecord->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
                    }

                    $closedAtDate = $pullRequest['closed_at'];
                    if ($closedAtDate !== null) {
                        $pullRequestRecord->closed_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $closedAtDate)->toDateTimeString();
                    }

                    $mergedAtDate = $pullRequest['merged_at'];
                    if ($mergedAtDate !== null) {
                        $pullRequestRecord->merged_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $mergedAtDate)->toDateTimeString();
                    }
                    $pullRequestRecord->merge_commit_sha = $pullRequest['merge_commit_sha'];

                    // head
                    if (isset($pullRequest['head']['user'])) {
                        $user = $this->userParser->userExistsOrCreate($pullRequest['head']['user']);
                        $pullRequestRecord->head_user_id = $user->id;
                    }

                    if (isset($pullRequest['head'])) {

                        try {
                            $headRepository = $this->repositoryParser->repositoryExistsOrCreate($pullRequest['head']['repo']['owner']['login'], $pullRequest['head']['repo']['name']);
                            $pullRequestRecord->head_repository_id = $headRepository->id;

                            if (isset($pullRequest['head']['repo'])) {
                                $pullRequestRecord->head_full_name = $pullRequest['head']['repo']['full_name'] ?? null;
                            }
                        } catch (Throwable $e) {
                            $this->writeToTerminal('Repository '.$pullRequest['head']['sha'].' does not exist anymore', 'info-red');
                        }

                        $pullRequestRecord->head_sha = $pullRequest['head']['sha'];
                        $pullRequestRecord->head_ref = $pullRequest['head']['ref']; // branch name
                    }

                    // base, always exist
                    $user = $this->userParser->userExistsOrCreate($pullRequest['base']['user']);
                    $pullRequestRecord->base_user_id = $user->id;
                    $baseRepository = $this->repositoryParser->repositoryExistsOrCreate($pullRequest['base']['repo']['owner']['login'], $pullRequest['base']['repo']['name']);
                    $pullRequestRecord->base_repository_id = $baseRepository->id;
                    $pullRequestRecord->base_ref = $pullRequest['base']['ref'];
                    $pullRequestRecord->base_sha = $pullRequest['base']['sha'];
                    $pullRequestRecord->base_full_name = $pullRequest['base']['repo']['full_name'];

                    $pullRequestRecord->title = $pullRequest['title'];
                    $pullRequestRecord->body = $pullRequest['body'];
                    $pullRequestRecord->html_url = $pullRequest['html_url'];
                    $pullRequestRecord->url = $pullRequest['url'];

                    if ($pullRequestRecord->save()) {
                        // save and link the commits of the pull request
                        $this->commitParser->getCommits($repository, $pullRequestRecord);
                        $this->writeToTerminal('Pull request: '.$pullRequest['id'].' saved ('.$pullRequestCounter.').');
                        $pullRequestCounter++;
                    }

                }
                else {
                    $this->writeToTerminal('Pull request: '.$pullRequest['id'].' already exists, skipping.');
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

        return $pullRequestCounter;



    }



}
