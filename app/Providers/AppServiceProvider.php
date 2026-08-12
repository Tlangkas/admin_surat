<?php

namespace App\Providers;

use App\Models\LetterRequest;
use App\Observers\LetterRequestObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(\Illuminate\Console\Command::class, function (\Illuminate\Console\Command $command, $app) {
            $command->setLaravel($app);
        });
    }

    public function boot(): void
    {
        LetterRequest::observe(LetterRequestObserver::class);
    }
}