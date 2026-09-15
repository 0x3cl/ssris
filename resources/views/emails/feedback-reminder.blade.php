@component('mail::message')
# We'd love to hear from you

Hi {{ $clientName }},

Thank you for completing your **{{ $service }}** request with PTRI ({{ $referenceNo }}). We would appreciate a few minutes of your time to share your feedback about your experience.

Your feedback helps us improve our services for future clients.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
