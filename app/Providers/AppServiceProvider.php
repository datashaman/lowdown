<?php

namespace App\Providers;

use Cache\Adapter\Redis\RedisCachePool;
use Github\Client as GitHubClient;
use Illuminate\Support\ServiceProvider;
use Redis;

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
     */
    public function register()
    {
        $this->app->singleton('github', function ($app) {
            $gitHub = new GitHubClient();

            if (config('lowdown.gists.cached')) {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);

                $pool = new RedisCachePool($redis);

                $gitHub->addCache($pool);
            }

            $gitHub->authenticate(
                config('lowdown.gists.token'),
                null,
                GitHubClient::AUTH_URL_TOKEN
            );

            return $gitHub;
        });
    }
}
