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


    public function userExistsOrCreate(int $id): User
    {
        $user = User::where('id', '=', $id)->first();
        if (!$user instanceof User) {
            // get user and save
            try {
                $userFromRequest = $this->client->api('user')->showById($id);
            } catch (RuntimeException $e) {
                $this->output->writeln('User ' . $id . ' does not exist!');
                return User::find(0);
            }

            $this->checkRemainingRequests($userFromRequest->getHeaders());

            // save to DB
            $user = new User();
            $user->id = $userFromRequest['id'];
            $user->login = $userFromRequest['login'];
            $user->node_id = $userFromRequest['node_id'];
            $user->name = $userFromRequest['name'];
            $user->company = $userFromRequest['company'];
            $user->location = $userFromRequest['location'];
            $user->email = $userFromRequest['email'];
            $user->html_url = $userFromRequest['html_url'];
            $user->save();
            $this->writeToTerminal('User ('.$user->login.') created');
        }
        else {
            //$this->writeToTerminal('User ('.$user->login.') already exists');
        }
        return $user;
    }



}
