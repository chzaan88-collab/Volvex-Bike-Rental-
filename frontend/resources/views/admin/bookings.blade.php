<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Admin Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-950 font-sans text-white">
    @include('partials.sidebar')
    <div class="lg:pl-[280px] min-h-screen p-4 md:p-8 lg:p-10">
        @if (session('status'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
        @endif

        <h1 class="text-3xl font-black mb-8">All Bookings</h1>

        <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
            @forelse ($bookings as $booking)
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center px-6 py-4 border-b border-white/5 last:border-0 gap-2">
                <div>
                    <div class="font-bold">{{ $booking['bike_name'] }}</div>
                    <div class="text-sm text-gray-400">{{ $booking['start_date'] }} {{ $booking['start_time'] }} &rarr; {{ $booking['end_date'] }} {{ $booking['end_time'] }}</div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xs font-bold px-2 py-1 rounded-full {{ $booking['status'] === 'Approved' ? 'bg-emerald-900 text-emerald-300' : ($booking['status'] === 'Pending' ? 'bg-amber-900 text-amber-300' : 'bg-gray-800 text-gray-400') }}">{{ $booking['status'] }}</span>
                    <div class="font-bold">Rs. {{ number_format($booking['total_amount'], 2) }}</div>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center text-gray-400">No bookings in the system.</div>
            @endforelse
        </div>
    </div>
</body>
</html>
