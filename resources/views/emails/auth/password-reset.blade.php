@component('mail::message')
# 🔐 Réinitialisation de mot de passe

Bonjour {{ $userName }},

Vous avez demandé la réinitialisation de votre mot de passe.

@component('mail::button', ['url' => $resetUrl])
Réinitialiser mon mot de passe
@endcomponent

Ce lien expirera dans **{{ $expiresIn }}**.

Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent
