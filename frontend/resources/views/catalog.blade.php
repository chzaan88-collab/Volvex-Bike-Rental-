<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Catalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <header class="fixed top-0 left-0 lg:left-[280px] right-0 h-16 bg-white/80 backdrop-blur-xl z-30 flex items-center px-6 border-b border-gray-100">
            <form action="{{ route('catalog.index') }}" method="GET" class="flex-1 flex items-center">
                <span class="material-symbols-outlined mr-2 text-gray-400">search</span>
                <input type="text" name="q" value="{{ $search_term }}" placeholder="Search for bikes or locations..." class="w-full bg-transparent border-none focus:outline-none text-sm" />
            </form>
        </header>

        <main class="pt-16 flex-1 p-4 md:p-8 lg:p-10">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
            @endif

            <section class="mb-10">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Premium Fleet</span>
                <h1 class="text-4xl font-black mt-2">Find Your Perfect <span class="text-emerald-700">Urban Companion.</span></h1>
            </section>

            <form action="{{ route('catalog.index') }}" method="GET" class="bg-white shadow-lg rounded-full px-6 py-4 flex flex-wrap items-center gap-4 mb-10 border border-gray-100">
                <input type="text" name="q" value="{{ $search_term }}" placeholder="Name, model, or location" class="flex-1 bg-transparent border-none focus:outline-none min-w-[200px]" />
                <select name="model" class="bg-transparent border-none focus:outline-none">
                    <option value="">All Models</option>
                    @foreach ($available_models as $model)
                    <option value="{{ $model }}" @selected($model_filter === $model)>{{ $model }}</option>
                    @endforeach
                </select>
                <select name="sort" class="bg-transparent border-none focus:outline-none">
                    <option value="" @selected($sort_by === '')>Name</option>
                    <option value="price_low" @selected($sort_by === 'price_low')>Price: Low to High</option>
                    <option value="price_high" @selected($sort_by === 'price_high')>Price: High to Low</option>
                </select>
                <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white px-6 py-3 rounded-full font-bold text-sm">Search</button>
            </form>

            @if ($bikes->isEmpty())
            <div class="text-center py-20 text-gray-400">
                <span class="material-symbols-outlined text-5xl">motorcycle</span>
                <h3 class="text-xl font-bold text-gray-700 mt-4">No bikes available right now</h3>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                @foreach ($bikes as $bike)
                <div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all flex flex-col">
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                        <img class="w-full h-full object-cover" alt="{{ $bike->name }}" src="{{ $bike->image_url ?? 'https://placehold.co/600x450?text=' . urlencode($bike->name) }}" />
                        <span class="absolute top-3 left-3 bg-emerald-700/90 text-white text-[10px] px-3 py-1 rounded-full uppercase tracking-wider">Available Now</span>
                        @if(session('fastapi_token'))
                        <form action="{{ route('bikes.favorite', $bike->id) }}" method="POST" class="absolute top-3 right-3">
                            @csrf
                            <button type="submit" class="bg-white/90 hover:bg-white rounded-full w-9 h-9 flex items-center justify-center shadow-sm">
                                <span class="material-symbols-outlined {{ in_array($bike->id, $favorite_bike_ids ?? []) ? 'text-red-500' : 'text-gray-400' }} text-[20px]">{{ in_array($bike->id, $favorite_bike_ids ?? []) ? 'favorite' : 'favorite_border' }}</span>
                            </button>
                        </form>
                        @endif
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
                            @if(session('fastapi_token'))
                            <a href="{{ route('booking.index', ['bike' => $bike->id]) }}" class="bg-gray-900 text-white px-5 py-3 rounded-lg text-sm font-bold uppercase hover:bg-emerald-700 transition-colors">Book Ride</a>
                            @else
                            <a href="{{ route('login') }}" class="bg-gray-900 text-white px-5 py-3 rounded-lg text-sm font-bold uppercase hover:bg-emerald-700 transition-colors">Login to Book</a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-center text-sm text-gray-400 mt-10">Viewing {{ $bikes->count() }} motorbikes</p>
            @endif
        </main>
    </div>
</body>
</html>
