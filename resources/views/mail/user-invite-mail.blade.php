<x-mail::message>
# {{ __('mail.invite_title') }}

{{ __('mail.invite_body', ['customer' => $customerName, 'app' => strtoupper(config('app.name'))]) }}

<x-mail::button :url="$url">
{{ __('mail.invite_button') }}
</x-mail::button>

{{ __('mail.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
