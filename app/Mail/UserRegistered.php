<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('👋 Bienvenue sur ' . config('app.name'))
                    ->markdown('emails.auth.registered')
                    ->with([
                        'userName' => $this->user->prenom,
                        'appName' => config('app.name'),
                        'registeredAt' => $this->user->created_at->format('d/m/Y à H:i'),
                    ]);
    }
}
