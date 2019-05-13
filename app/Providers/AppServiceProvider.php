<?php

namespace Datashaman\Lowdown\Providers;

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

            if (env('LOWDOWN_GISTS_CACHED')) {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);

                $pool = new RedisCachePool($redis);

                $gitHub->addCache($pool);
            }

            $gitHub->authenticate(
                env('LOWDOWN_GISTS_TOKEN'),
                null,
                GitHubClient::AUTH_URL_TOKEN
            );

            return $gitHub;
        });
    }
}
