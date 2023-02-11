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
            $owner = $this->userParser->userExistsOrCreate($repositoryFromRequest['owner']['id']);
            $repository = new Repository();
            $repository->id = $repositoryFromRequest['id'];
            $repository->node_id = $repositoryFromRequest['node_id'];
            $repository->name = $repositoryFromRequest['name'];
            $repository->full_name = $repositoryFromRequest['full_name'];
            $repository->owner_id = $owner->id;
            $repository->description = $repositoryFromRequest['description'];
            $repository->html_url = $repositoryFromRequest['html_url'];
            $repository->default_branch = $repositoryFromRequest['default_branch'];
            $repository->is_fork = $repositoryFromRequest['fork'] ?? false;

            $createdAtDate = $repositoryFromRequest['created_at'];
            if ($createdAtDate !== null) {
                $repository->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $createdAtDate)->toDateTimeString();
            }

            $updatedAtDate = $repositoryFromRequest['updated_at'];
            if ($updatedAtDate !== null) {
                $repository->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $updatedAtDate)->toDateTimeString();
            }

            $repository->save();
            $created = true;
        }

        $this->writeToTerminal(($created) ? sprintf('Repository (%s) created', $repository->full_name) : sprintf('Repository (%s) already exists', $repository->full_name));
        return $repository;
    }

}
