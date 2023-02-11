<?php

namespace App\Providers;

use Github\AuthMethod;
use Github\Client;
use Illuminate\Support\ServiceProvider;



use Symfony\Component\Console\Output\Output;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\ConsoleOutput;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function ($app) {
            $client = new Client();
            $client->authenticate(env('GITHUB_TOKEN'), null, AuthMethod::ACCESS_TOKEN);
            return $client;
        });

        $this->app->singleton(Output::class, function ($app) {
            return new ConsoleOutput();
        });

    }
}
