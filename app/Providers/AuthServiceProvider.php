<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return "http://localhost:3000/reset-password?token=$token&email={$user->email}";
        });
    }
}
