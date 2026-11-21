<x-mail::message>
# {{ __('member-portal.mail.'.$purpose.'.title') }}

{{ __('member-portal.mail.'.$purpose.'.body') }}

@if ($loginId)
**{{ __('member-portal.ui.login_id') }}**  
{{ $loginId }}
@endif

<x-mail::button :url="$url">
{{ __('member-portal.mail.'.$purpose.'.button') }}
</x-mail::button>
</x-mail::message>
