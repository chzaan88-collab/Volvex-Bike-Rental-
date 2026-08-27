<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <main class="flex-1 p-4 md:p-8 lg:p-10 max-w-4xl mx-auto w-full">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
            @endif

            <header class="mb-8">
                <h1 class="text-3xl md:text-4xl font-black">Notifications</h1>
                <p class="text-gray-500 mt-2">Stay updated on your bookings and rides.</p>
            </header>

            <div class="space-y-4">
                @forelse ($notifications as $notif)
                <div class="bg-white border {{ $notif['class'] === 'success' ? 'border-emerald-200' : ($notif['class'] === 'warning' ? 'border-amber-200' : ($notif['class'] === 'danger' ? 'border-red-200' : 'border-gray-100')) }} rounded-xl p-4 md:p-5 flex items-start gap-4 shadow-sm">
                    <span class="material-symbols-outlined mt-0.5 {{ $notif['class'] === 'success' ? 'text-emerald-600' : ($notif['class'] === 'warning' ? 'text-amber-600' : ($notif['class'] === 'danger' ? 'text-red-600' : 'text-blue-600')) }}">
                        {{ $notif['class'] === 'success' ? 'check_circle' : ($notif['class'] === 'warning' ? 'schedule' : ($notif['class'] === 'danger' ? 'cancel' : 'info')) }}
                    </span>
                    <div class="flex-1">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-bold">{{ $notif['title'] }}</h3>
                            <span class="text-xs text-gray-400 shrink-0">{{ $notif['time'] }}</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">{{ $notif['message'] }}</p>
                        @if (($notif['booking_id'] ?? 0) > 0 && $notif['class'] === 'success')
                        <a href="{{ route('booking.index') }}" class="inline-block mt-3 text-emerald-700 text-sm font-bold">View Booking</a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="bg-white border border-gray-100 rounded-xl p-12 text-center text-gray-400">
                    <span class="material-symbols-outlined text-5xl">notifications_off</span>
                    <h3 class="text-lg font-bold text-gray-600 mt-4">No notifications yet</h3>
                    <p class="text-sm mt-1">You're all caught up!</p>
                </div>
                @endforelse
            </div>
        </main>
    </div>
</body>
</html>
