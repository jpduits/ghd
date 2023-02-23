<?php

namespace App\Parsers;

use Github\Client;
use Carbon\Carbon;
use App\Models\Repository;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\Output;


class RepositoryParser extends BaseParser
{

    private UserParser $userParser;


    public function __construct(UserParser $userParser, Client $client, Output $output)
    {
        parent::__construct($client, $output);
        $this->userParser = $userParser;
    }

    public function repositoryExistsOrCreate(string $ownerName, string $repositoryName) : Repository
    {
        $created = false;
        $repository = Repository::where('full_name', '=', $ownerName.'/'.$repositoryName)->first();
        if (!$repository instanceof Repository) {

            $repositoryFromRequest = $this->client->api('repo')->show($ownerName, $repositoryName);
            $headers = $this->client->getLastResponse()->getHeaders();
            $this->checkRemainingRequests($headers);

            $owner = $this->userParser->userExistsOrCreate($repositoryFromRequest['owner']);

            $repository = new Repository();
            $repository->github_id = $repositoryFromRequest['id'];
            $repository->owner_id = $owner->id;
            $repository->name = $repositoryFromRequest['name'];
            $repository->full_name = $repositoryFromRequest['full_name'];

            $repository->default_branch = $repositoryFromRequest['default_branch'];
            $repository->language = $repositoryFromRequest['language'] ?? null;
            $repository->is_fork = $repositoryFromRequest['fork'] ?? false;
            $repository->forks_count = $repositoryFromRequest['forks_count'] ?? false;
            $repository->stargazers_count = $repositoryFromRequest['stargazers_count'] ?? false;
            $repository->subscribers_count = $repositoryFromRequest['subscribers_count'] ?? false;

            $repository->description = $repositoryFromRequest['description'];
            $repository->html_url = $repositoryFromRequest['html_url'];
            $repository->url = $repositoryFromRequest['url'];

            $createdAtDate = $repositoryFromRequest['created_at'];
            if ($createdAtDate !== null) {
                $repository->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
            }

            $updatedAtDate = $repositoryFromRequest['updated_at'];
            if ($updatedAtDate !== null) {
                $repository->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
            }

            $pushedAtDate = $repositoryFromRequest['pushed_at'];
            if ($pushedAtDate !== null) {
                $repository->pushed_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $pushedAtDate)->toDateTimeString();
            }

            $repository->save();
            $created = true;
        }

        $this->writeToTerminal(($created) ? sprintf('Repository (%s) created', $repository->full_name) : sprintf('Repository (%s) already exists', $repository->full_name));
        return $repository;
    }

}
