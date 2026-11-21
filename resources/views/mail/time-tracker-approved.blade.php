<x-mail::message>
# {{ __('mail.approved_title') }}

{{ __('mail.approved_greeting', ['name' => $userName]) }}

{{ __('mail.approved_body', ['tracker' => $trackerName, 'amount' => $amount]) }}

{{ __('mail.approved_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
