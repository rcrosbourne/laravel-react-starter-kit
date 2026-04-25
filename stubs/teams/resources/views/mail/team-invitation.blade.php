<x-mail::message>
# You're invited to join {{ $team->name }}

Click the button below to accept the invitation. This link is signed and will expire if tampered with.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

If you did not expect this invitation, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
