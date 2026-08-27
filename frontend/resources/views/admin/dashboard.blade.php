<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-950 font-sans text-white">
    @include('partials.sidebar')
    <div class="lg:pl-[280px] min-h-screen p-4 md:p-8 lg:p-10">
        @if (session('status'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
        @endif

        <h1 class="text-3xl font-black mb-8">Admin Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white/5 border border-white/10 p-6 rounded-xl">
                <div class="text-3xl font-black">{{ $total_bikes }}</div>
                <div class="text-sm text-gray-400">Total Bikes</div>
            </div>
            <div class="bg-white/5 border border-white/10 p-6 rounded-xl">
                <div class="text-3xl font-black">{{ $total_bookings }}</div>
                <div class="text-sm text-gray-400">Bookings</div>
            </div>
            <div class="bg-emerald-900 p-6 rounded-xl">
                <div class="text-3xl font-black">Rs. {{ number_format($total_earnings, 2) }}</div>
                <div class="text-sm text-emerald-300">Revenue</div>
            </div>
        </div>

        <div class="flex gap-4 mb-6">
            <a href="{{ route('admin.bikes') }}" class="bg-emerald-700 text-white px-5 py-2 rounded-lg font-bold text-sm">Manage Bikes</a>
            <a href="{{ route('admin.bookings') }}" class="bg-white/10 text-white px-5 py-2 rounded-lg font-bold text-sm">View Bookings</a>
        </div>

        <h2 class="text-lg font-bold mb-4">Recent Bookings</h2>
        <div class="space-y-3">
            @forelse ($recent_bookings as $booking)
            <div class="bg-white/5 border border-white/10 rounded-xl p-4 flex justify-between items-center">
                <div>
                    <div class="font-bold">{{ $booking['bike_name'] }}</div>
                    <div class="text-sm text-gray-400">{{ $booking['start_date'] }} &middot; {{ $booking['status'] }}</div>
                </div>
                <div class="font-bold">Rs. {{ number_format($booking['total_amount'], 2) }}</div>
            </div>
            @empty
            <p class="text-gray-400">No bookings yet.</p>
            @endforelse
        </div>
    </div>
</body>
</html>
