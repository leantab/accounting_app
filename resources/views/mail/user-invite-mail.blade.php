<x-mail::message>
# Invitación a usar la plataforma

Te invitaron a unirte a {{ $customerName }}
en {{ strtoupper(config('app.name')) }}.

<x-mail::button :url="$url">
Aceptar invitación
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
