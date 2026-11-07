<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Mail::extend('gmail_api', function (array $config): GmailApiTransport {
            return new GmailApiTransport(
                app(Factory::class),
                (string) config('services.google.gmail_client_id', ''),
                (string) config('services.google.gmail_client_secret', ''),
                (string) config('services.google.gmail_refresh_token', ''),
            );
        });
    }
}
