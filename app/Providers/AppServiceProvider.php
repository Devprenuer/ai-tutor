<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI;
use App\Services\Ai\AiClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenAI::class, function ($app) {
            return OpenAI::client(config('services.openai.api_key'));
        });

        $this->app->singleton(AiClient::class, function ($app) {
            return new AiClient($app->make(OpenAI::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
