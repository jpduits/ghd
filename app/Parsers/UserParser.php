<?php

namespace App\Parsers;

use Github\Client;
use App\Models\User;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Output\Output;

class UserParser extends BaseParser
{

    public function __construct(Client $client, Output $output)
    {
        parent::__construct($client, $output);
    }

    public function userExistsOrCreate(array $user): User
    {

        if (isset($user['id'])) {
            // return a user based on the github id
            return $this->gitHubUserExistsOrCreate($user['id']);
        }
        else if (isset($user['name']) && isset($user['email'])) {
            // return a user based on the name and email (non github user)
            return $this->nonGithubUserExistsOrCreate($user['name'], $user['email']);
        }

        // return a dummy user
        return User::find(0);
    }


    private function gitHubUserExistsOrCreate(int $gitHubId): User
    {
        $user = User::where('github_id', '=', $gitHubId)->first();
        if (!$user instanceof User) {
            // get user and save
            try {
                $userFromRequest = $this->client->api('user')->showById($gitHubId);
            } catch (RuntimeException $e) {
                $this->output->writeln('User ' . $gitHubId . ' does not exist!');
                return User::find(0);
            }

            $headers = $this->client->getLastResponse()->getHeaders();
            $this->checkRemainingRequests($headers);

            // save to DB
            $user = new User();
            $user->github_id = $userFromRequest['id'];
            $user->login = $userFromRequest['login'];
            $user->name = $userFromRequest['name'];
            $user->company = $userFromRequest['company'];
            $user->location = $userFromRequest['location'];
            $user->email = $userFromRequest['email'];
            $user->url = $userFromRequest['url'];
            $user->html_url = $userFromRequest['html_url'];
            $user->save();
            $this->writeToTerminal('GitHub user (' . $user->login . ') created');
        }

        return $user;
    }


    private function nonGithubUserExistsOrCreate(string $name, string $email): User
    {
        $user = User::where('email', '=', $email)->where('name', '=', $name)->first();
        if (!$user instanceof User) {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->save();
            $this->writeToTerminal('Non GitHub user (' . $user->name . ') created');
        }

        return $user;
    }

}
