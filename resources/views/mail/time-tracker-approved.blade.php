<x-mail::message>
# ¡Tu planilla fue aprobada!

Hola {{ $userName }},

Ya fue aprobada tu planilla **{{ $trackerName }}**. Ya podés armar tu factura por un total de **{{ $amount }}** y enviarla por email.

¡Gracias por tu trabajo!<br>
{{ config('app.name') }}
</x-mail::message>
