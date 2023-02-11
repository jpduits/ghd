<?php

namespace App\Parsers;

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
                // first check stargazer exists
                $pullRequestRecord = PullRequest::find($pullRequest['id']);
                if (!$pullRequestRecord instanceof PullRequest) {
                    // Commit does not exist, create
                    $pullRequestRecord = new PullRequest();

                    $pullRequestRecord->id = $pullRequest['id'];
                    $pullRequestRecord->node_id = $pullRequest['node_id'];
                    $pullRequestRecord->url = $pullRequest['url'];
                    $pullRequestRecord->state = $pullRequest['state'];
                    $pullRequestRecord->number = $pullRequest['number'];
                    $pullRequestRecord->title = $pullRequest['title'];
                    $pullRequestRecord->body = $pullRequest['body'];
                    $pullRequestRecord->merge_commit_sha = $pullRequest['merge_commit_sha'];
                    $mergeCommit = Commit::where('sha', '=', $pullRequest['merge_commit_sha'])->first();
                    if ($mergeCommit instanceof Commit) {
                        // there is a merge commit, so the commits are saved under the selected branch
                        $pullRequestRecord->merge_commit_id = $mergeCommit->id;
                    }

                    $user = $this->userParser->userExistsOrCreate($pullRequest['user']['id']);
                    $pullRequestRecord->user_id = $user->id;

                    $closedAtDate = $pullRequest['closed_at'];
                    if ($closedAtDate !== null) {
                        $pullRequestRecord->closed_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $closedAtDate)->toDateTimeString();
                    }

                    $createdAtDate = $pullRequest['created_at'];
                    if ($createdAtDate !== null) {
                        $pullRequestRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
                    }

                    $updatedAtDate = $pullRequest['updated_at'];
                    if ($updatedAtDate !== null) {
                        $pullRequestRecord->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
                    }

                    $mergedAtDate = $pullRequest['merged_at'];
                    if ($mergedAtDate !== null) {
                        $pullRequestRecord->merged_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $mergedAtDate)->toDateTimeString();
                    }

                    if (isset($pullRequest['head']['user']['id'])) {
                        $user = $this->userParser->userExistsOrCreate($pullRequest['head']['user']['id']);
                        $pullRequestRecord->head_user_id = $user->id;
                    }

                    if (isset($pullRequest['head']['repo']['full_name'])) {

                        try {

                            $mainRepository = $this->repositoryParser->repositoryExistsOrCreate($pullRequest['head']['repo']['owner']['login'], $pullRequest['head']['repo']['name']);
                            $pullRequestRecord->head_repository_id = $mainRepository->id;
                            $pullRequestRecord->head_ref = $pullRequest['head']['ref'];

                            // save commits of not merged pull request
                            if ($pullRequest['merge_commit_sha'] == null) {
                                // Pull request not merged, try to get the commits and users.
                                $commitCount = $this->commitParser->getCommits($repository, $pullRequest['number']);
                                $this->writeToTerminal($commitCount.' commits saved for not merged pull request.');
                            }

                        } catch (RuntimeException $e) {
                            $this->writeToTerminal('Repository '.$pullRequest['head']['repo']['full_name'].' does not exist anymore, commits not saved');
                        }

                    }

                    $user = $this->userParser->userExistsOrCreate($pullRequest['base']['user']['id']);
                    $pullRequestRecord->base_user_id = $user->id;

                    $baseRepository = $this->repositoryParser->repositoryExistsOrCreate($pullRequest['base']['repo']['owner']['login'], $pullRequest['base']['repo']['name']);
                    $pullRequestRecord->base_repository_id = $baseRepository->id;
                    $pullRequestRecord->base_ref = $pullRequest['base']['ref'];


                    $pullRequestRecord->save();
                    $pullRequestCounter++;
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
