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

    public function userExistsOrCreate(array $user, bool $requestMetaData = false): User
    {

        if (isset($user['id'])) {
            // return a user based on the github id
            return $this->gitHubUserExistsOrCreate($user, $requestMetaData);
        }
        else if (isset($user['name']) && isset($user['email'])) {
            // return a user based on the name and email (non github user)
            return $this->nonGithubUserExistsOrCreate($user['name'], $user['email']);
        }

        // return a dummy user
        return User::find(1);
    }


    private function gitHubUserExistsOrCreate(array $userData, bool $requestMetaData): User
    {
        $user = User::where('github_id', '=', $userData['id'])->first();
        if (!$user instanceof User) {

            // save only ID and login
            if (!$requestMetaData) {
                $user = new User();
                $user->github_id = $userData['id'];
                $user->login = $userData['login'];
                $user->save();
                $this->writeToTerminal('GitHub user (' . $user->login . ') created');
                return $user;
            }

            // get user and save
            try {
                $userFromRequest = $this->client->api('user')->showById($userData['id']);
            } catch (RuntimeException $e) {
                $this->output->writeln('User ' . $userData['id'] . ' does not exist!');
                return User::find(1);
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
