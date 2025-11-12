<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PasswordResetRequested extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;

    public function __construct(User $user, $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
    }

    public function build()
    {
        return $this->subject('🔐 Réinitialisation de votre mot de passe - ' . config('app.name'))
                    ->markdown('emails.auth.password-reset')
                    ->with([
                        'userName' => $this->user->prenom,
                        'resetUrl' => $this->resetUrl,
                        'expiresIn' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire') . ' minutes',
                    ]);
    }
}
