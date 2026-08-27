<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex My Bikes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50">
    @include('partials.sidebar')

    <!-- Main Content Wrapper -->
    <div class="lg:pl-[280px] min-h-screen w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8 lg:py-10">

            <!-- Status Messages -->
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-xl flex-shrink-0">check_circle</span>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600 text-xl flex-shrink-0">error</span>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <!-- Page Header with Action Button -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900">My Bikes</h1>
                    <p class="text-gray-500 mt-2 text-sm md:text-base">Manage your fleet.</p>
                </div>
                <a href="{{ route('owner.bikes.create') }}"
                   class="inline-flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md whitespace-nowrap w-full md:w-auto">
                    <span class="material-symbols-outlined text-lg">add</span>
                    <span>Add Bike</span>
                </a>
            </div>

            <!-- Bikes Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                @forelse ($bikes as $bike)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-200 group">
                    <!-- Bike Image -->
                    <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                        <img src="{{ $bike->image_url ?? 'https://placehold.co/400x250?text=' . urlencode($bike->name) }}"
                             alt="{{ $bike->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold shadow-sm
                            @if($bike->status === 'available')
                                bg-emerald-600 text-white
                            @else
                                bg-amber-500 text-white
                            @endif
                        ">
                            {{ ucfirst($bike->status) }}
                        </span>
                    </div>

                    <!-- Bike Details -->
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 line-clamp-1">{{ $bike->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-gray-400 text-sm">location_on</span>
                            <span class="truncate">{{ $bike->city }}</span>
                            <span class="text-gray-300">&middot;</span>
                            <span class="truncate">{{ $bike->model }}</span>
                        </p>

                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                            <div>
                                <p class="text-emerald-700 font-bold text-lg">Rs. {{ number_format($bike->hourly_rate, 2) }}<span class="text-sm font-medium text-gray-500">/hr</span></p>
                            </div>

                            <form action="{{ route('owner.bikes.delete', $bike->id) }}" method="POST" onsubmit="return confirm('Delete this bike?')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 text-sm font-bold px-3 py-2 rounded-lg transition-colors duration-200">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                        <span class="material-symbols-outlined text-5xl text-gray-400">motorcycle</span>
                    </div>
                    <p class="text-gray-500 font-medium text-lg">No bikes yet.</p>
                    <p class="text-gray-400 text-sm mt-2">Add your first bike to start renting.</p>
                    <a href="{{ route('owner.bikes.create') }}"
                       class="inline-flex items-center gap-2 mt-6 bg-emerald-700 hover:bg-emerald-800 text-white px-6 py-3 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md">
                        <span class="material-symbols-outlined text-lg">add</span>
                        <span>Add your first bike</span>
                    </a>
                </div>
                @endforelse
            </div>

        </div>
    </div>
</body>
</html>
