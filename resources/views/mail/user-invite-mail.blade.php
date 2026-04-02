<x-mail::message>
# Invitacion a usar la plataforma

Has sido invitado a unirte a {{ $customerName }}
en {{ strtoupper(config('app.name')) }}

<x-mail::button :url="$url">
Aceptar invitacion
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
