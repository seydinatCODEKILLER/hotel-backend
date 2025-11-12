@component('mail::message')
# 👋 Bienvenue {{ $userName }} !

Votre compte a été créé sur **{{ $appName }}** le **{{ $registeredAt }}**.

@component('mail::button', ['url' => url('/dashboard')])
Accéder à mon compte
@endcomponent

Cordialement,<br>
L'équipe {{ $appName }}
@endcomponent
