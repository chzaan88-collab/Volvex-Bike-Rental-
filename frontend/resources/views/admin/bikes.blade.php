<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Admin Bikes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-950 font-sans text-white">
    @include('partials.sidebar')
    <div class="lg:pl-[280px] min-h-screen p-4 md:p-8 lg:p-10">
        @if (session('status'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
        @endif

        <h1 class="text-3xl font-black mb-8">All Bikes</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
            @forelse ($bikes as $bike)
            <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden">
                <img src="{{ $bike->image_url ?? 'https://placehold.co/400x250' }}" alt="{{ $bike->name }}" class="w-full h-40 object-cover" />
                <div class="p-5">
                    <h3 class="font-bold text-lg">{{ $bike->name }}</h3>
                    <p class="text-sm text-gray-400">{{ $bike->city }} &middot; {{ $bike->status }}</p>
                    <p class="text-emerald-400 font-bold mt-2">Rs. {{ number_format($bike->hourly_rate, 2) }}/hr</p>
                </div>
            </div>
            @empty
            <p class="text-gray-400 col-span-3 text-center py-12">No bikes in the system.</p>
            @endforelse
        </div>
    </div>
</body>
</html>
