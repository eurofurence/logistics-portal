<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('general.order_approval_reminder', ['count' => $orders->count()], 'en') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; padding: 24px;">
    <h1>{{ $siteName }}</h1>
    <p>{{ __('general.order_approval_reminder_greeting', ['name' => $username], 'en') }}</p>
    <h2>{{ __('general.order_approval_reminder', ['count' => $orders->count()], 'en') }}</h2>
    <p>{{ __('general.order_approval_reminder_body', [], 'en') }}</p>
    <ul>
        @foreach ($orders as $order)
            <li style="margin-bottom: 16px;">
                <a href="{{ route('filament.app.resources.orders.view', $order) }}">#{{ $order->id }} — {{ $order->name }}</a><br>
                {{ __('general.department', [], 'en') }}: {{ $order->department?->name }}<br>
                {{ __('general.order_event', [], 'en') }}: {{ $order->event?->name }}
            </li>
        @endforeach
    </ul>
    <p>{{ __('general.order_approval_reminder_once', [], 'en') }}</p>
</body>
</html>
