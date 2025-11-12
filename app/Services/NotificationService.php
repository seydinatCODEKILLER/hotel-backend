<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\UserRegistered;
use App\Mail\PasswordResetRequested;
use Exception;

class NotificationService
{
    public function sendUserRegisteredNotification($user)
    {
        try {
            Mail::to($user->email)->queue(new UserRegistered($user));
            Log::info('User registration email queued', ['user_id' => $user->id]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to send registration email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendPasswordResetNotification($user, $resetUrl)
    {
        try {
            Mail::to($user->email)->queue(new PasswordResetRequested($user, $resetUrl));
            Log::info('Password reset email queued', ['user_id' => $user->id]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
