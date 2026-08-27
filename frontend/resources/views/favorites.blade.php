<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Favorites</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <main class="flex-1 p-4 md:p-8 lg:p-10 max-w-6xl mx-auto w-full">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
            @endif

            <header class="mb-10">
                <h1 class="text-4xl font-black">My Favorites</h1>
                <p class="text-gray-500 mt-2">Bikes you've saved for later.</p>
            </header>

            @if ($bikes->isEmpty())
            <div class="bg-white border border-gray-100 rounded-xl p-12 text-center text-gray-400">
                <span class="material-symbols-outlined text-5xl">favorite_border</span>
                <h3 class="text-lg font-bold text-gray-600 mt-4">No favorites yet</h3>
                <p class="text-sm mt-1">Browse the catalog and save bikes you like.</p>
                <a href="{{ route('catalog.index') }}" class="inline-block mt-4 bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-bold text-sm">Browse Bikes</a>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                @foreach ($bikes as $bike)
                <div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all flex flex-col">
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                        <img class="w-full h-full object-cover" alt="{{ $bike->name }}" src="{{ $bike->image_url ?? 'https://placehold.co/600x450?text=' . urlencode($bike->name) }}" />
                        <span class="absolute top-3 left-3 bg-emerald-700/90 text-white text-[10px] px-3 py-1 rounded-full uppercase tracking-wider">{{ $bike->status }}</span>
                        <form action="{{ route('bikes.favorite', $bike->id) }}" method="POST" class="absolute top-3 right-3">
                            @csrf
                            <button type="submit" class="bg-white/90 hover:bg-white rounded-full w-9 h-9 flex items-center justify-center shadow-sm">
                                <span class="material-symbols-outlined text-red-500 text-[20px]">favorite</span>
                            </button>
                        </form>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="text-xl font-bold">{{ $bike->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $bike->model }} &middot; Plate {{ $bike->license }}</p>
                        <p class="text-sm text-gray-500 mt-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">pin_drop</span>{{ $bike->last_known_address }}
                        </p>
                        <div class="mt-auto pt-4 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-gray-400 uppercase">Rate</span>
                                <div class="text-2xl font-bold text-emerald-700">Rs. {{ number_format($bike->hourly_rate, 0) }}<span class="text-sm text-gray-400 font-normal">/hr</span></div>
                            </div>
                            <a href="{{ route('booking.index', ['bike' => $bike->id]) }}" class="bg-gray-900 text-white px-5 py-3 rounded-lg text-sm font-bold uppercase hover:bg-emerald-700 transition-colors">Book Ride</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </main>
    </div>
</body>
</html>
