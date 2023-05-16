<?php

namespace App\Providers;

use App\Services\Blizzard\BlizzardAuthClient;
use App\Services\Blizzard\BlizzardProfileClient;
use App\Services\BlizzardAuthService;
use Illuminate\Support\ServiceProvider;

class BlizzardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BlizzardAuthClient::class, function () {
            return new BlizzardAuthClient(config('blizzard.client.id'), config('blizzard.client.secret'));
        });

        $this->app->singleton(BlizzardProfileClient::class, function () {
            $token = cache('token');

            if(empty($token)) {
                $blizzardAuthService = app(BlizzardAuthService::class);
                $token = $blizzardAuthService->refreshAndCacheAccessToken();
            }

            return new BlizzardProfileClient($token);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
