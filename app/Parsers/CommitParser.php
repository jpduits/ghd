<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\PullRequest;
use TiagoHillebrandt\ParseLinkHeader;
use Symfony\Component\Console\Output\Output;
use Github\HttpClient\Message\ResponseMediator;

class CommitParser extends BaseParser
{

    private UserParser $userParser;
    private RepositoryParser $repositoryParser;

    public function __construct(Client $client, Output $output, UserParser $userParser, RepositoryParser $repositoryParser)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
        $this->repositoryParser = $repositoryParser;
    }

    public function getCommits(Repository $repository, PullRequest $pullRequest = null): Int
    {
        $commitCounter = 0;

        $page = 1;
        $lastPage = 1;

        $suffix = '';
        if ($pullRequest instanceof PullRequest) {
            $uri = 'repos/' . $repository->full_name . '/pulls/'.$pullRequest->number.'/commits?per_page=100';
            $suffix = ' (pull Request '.$pullRequest->number.')';
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

                    // if commit sha not exists, and it is from a pull request, the pull request is not merged
                    // set repository_id to head repository (the original)
                    if (($pullRequest instanceof PullRequest) && ($pullRequest->merged_at == null)) {

                        try {

                            [$repoOwner, $repoName] = explode('/', $pullRequest->head_full_name);
                            $headRepo = $this->repositoryParser->repositoryExistsOrCreate($repoOwner, $repoName);
                            $commitRecord->repository_id = $headRepo->id;
                        }
                        catch (\Exception $e) {
                            $commitRecord->repository_id = null; // head repository is deleted
                            $this->writeToTerminal('Error (head repository does not exist): '.$e->getMessage(), 'info-red');
                        }

                    }
                    else {
                        $commitRecord->repository_id = $repository->id;
                    }

                    $commitRecord->sha = $commit['sha'];

                    $commitDate = $commit['commit']['author']['date']; // date of commit
                    $commitRecord->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $commitDate)->toDateTimeString();

                    if (isset($commit['commit']['author'])) {
                        $author = $this->userParser->userExistsOrCreate($commit['commit']['author']);
                    }
                    else {
                        //$author = $this->userParser->userExistsOrCreate(0); // user does not exist (deleted)
                    }
                    $commitRecord->author_id = $author->id;

                    if (isset($commit['commit']['committer'])) {
                        $committer = $this->userParser->userExistsOrCreate($commit['commit']['committer']);
                    }
                    else {
                        //$committer = $this->userParser->userExistsOrCreate(0); // user deleted
                    }
                    $commitRecord->committer_id = $committer->id;

                    $commitRecord->message = $commit['commit']['message'] ?? '';
                    $commitRecord->url = $commit['url'];
                    $commitRecord->html_url = $commit['html_url'];

                    $commitRecord->save();

                    // save parent commits
                    if (isset($commit['parents'])) {

                        foreach ($commit['parents'] as $parent) {
                            $parentCommit = Commit::where('sha', '=', $parent['sha'])->first();
                            if ($parentCommit instanceof Commit) {
                                $commitRecord->parents()->attach($parentCommit->id);
                            }
                        }
                    }

                    $commitCounter++;
                }
                else {
                    $this->writeToTerminal('Commit: '.$commit['sha'].' already exists, skipping.');
                }

                // link commit to pull request
                if ($pullRequest instanceof PullRequest) {
                    $this->writeToTerminal('Commit: '.$commit['sha'].' linking to pull request '.$pullRequest->number.'.', 'info-yellow');
                    $commitRecord->pullRequest()->attach($pullRequest->github_id);
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

        return $commitCounter;
    }


}
