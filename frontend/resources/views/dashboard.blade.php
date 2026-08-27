<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <main class="flex-1 p-4 md:p-8 lg:p-10 max-w-7xl mx-auto w-full">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded-xl p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm font-semibold rounded-xl p-4">{{ $errors->first() }}</div>
            @endif

            <header class="flex justify-between items-center mb-10">
                <div>
                    <span class="text-xs font-bold text-emerald-600 tracking-widest uppercase">RIDER STATUS: {{ $rider_status }}</span>
                    <h1 class="text-3xl font-black text-slate-900 mt-1">Welcome Back, {{ $user_name }}!</h1>
                    <p class="text-slate-500 text-sm font-medium">Ready for your next ride?</p>
                </div>
                <a href="{{ route('catalog.index') }}" class="bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-3 px-6 rounded-xl flex items-center gap-2 transition-colors">
                    Rent a Motorbike
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white border border-slate-100 p-6 rounded-3xl shadow-sm">
                    <div class="text-4xl font-black text-slate-900">{{ $active_rides_count }}</div>
                    <div class="text-xs font-bold text-slate-400 mt-1">Active Rides</div>
                </div>
                <div class="bg-white border border-slate-100 p-6 rounded-3xl shadow-sm">
                    <div class="text-4xl font-black text-slate-900">Rs. {{ number_format($current_balance, 2) }}</div>
                    <div class="text-xs font-bold text-slate-400 mt-1">Current Balance</div>
                </div>
                <div class="bg-slate-900 border border-slate-900 p-6 rounded-3xl shadow-sm text-white">
                    <div class="text-4xl font-black text-white">Rs. {{ number_format($lifetime_spend, 2) }}</div>
                    <div class="text-xs font-semibold text-slate-400 mt-1">Lifetime Spend</div>
                </div>
            </div>

            @if (isset($recommended_bikes) && $recommended_bikes->isNotEmpty())
            <div class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Recommended for You</h2>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Bikes near <span class="text-emerald-600 font-bold">{{ $user_location ?? 'your location' }}</span> — ranked by proximity</p>
                    </div>
                    <a href="{{ route('catalog.index') }}" class="text-emerald-700 hover:text-emerald-800 text-sm font-bold flex items-center gap-1">
                        View all
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($recommended_bikes->take(4) as $bike)
                    <a href="{{ route('booking', ['bike' => $bike->id]) }}" class="bg-white border border-slate-100 rounded-3xl overflow-hidden shadow-sm hover:shadow-md hover:border-emerald-200 transition-all group">
                        <div class="h-36 bg-slate-200 bg-cover bg-center" style="background-image: url('{{ $bike->image_url ?? 'https://placehold.co/400x300' }}');"></div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-extrabold text-slate-900 text-sm truncate">{{ $bike->name }}</h3>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">{{ $bike->city }}</span>
                            </div>
                            <p class="text-[11px] font-semibold text-slate-400 mt-1">{{ $bike->brand }} &middot; {{ $bike->model }}</p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-sm font-black text-slate-900">Rs. {{ number_format($bike->hourly_rate, 0) }}/hr</span>
                                <span class="text-emerald-700 text-xs font-bold group-hover:translate-x-0.5 transition-transform">Book now &rarr;</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2" id="active-rides">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Live Tracking</h2>
                    @if ($active_ride)
                    <div class="bg-white border border-slate-100 p-4 rounded-3xl shadow-sm flex flex-col md:flex-row gap-5">
                        <div class="w-full md:w-1/2 h-56 bg-slate-200 rounded-2xl relative flex flex-col justify-end p-3 overflow-hidden bg-cover bg-center" style="background-image: url('https://placeholder.pics/svg/400x300/E2E8F0/64748B/Map%20View');">
                            <div class="bg-white/95 backdrop-blur-sm shadow-sm rounded-xl p-3 text-[11px] font-bold text-slate-800 border border-slate-100">
                                Location: <span class="text-slate-500 font-medium">{{ $active_ride['city'] ?? $active_ride['address'] }}</span>
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col justify-between py-1">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-900">{{ $active_ride['bike_name'] }}</h3>
                                <p class="text-[11px] font-bold text-slate-400 mt-0.5 uppercase tracking-wider">
                                    {{ $active_ride['model'] }} &middot; {{ $active_ride['license'] }}
                                </p>
                                <span class="inline-block mt-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-lg border border-emerald-100">{{ $active_ride['status'] }}</span>
                                <div class="mt-4 bg-amber-50 border border-amber-100 text-amber-900 p-3.5 rounded-2xl">
                                    <div class="text-[10px] font-bold tracking-wider text-amber-700 uppercase">Due Return</div>
                                    <div class="text-xs font-semibold mt-0.5">{{ $active_ride['due_time'] }}</div>
                                </div>
                            </div>
                            @if ($active_ride['status'] === 'Approved')
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <form action="{{ route('rides.extend', $active_ride['id']) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="extra_hours" value="1">
                                    <button type="submit" class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 font-bold py-3 px-4 rounded-xl text-xs">Extend Time</button>
                                </form>
                                <form action="{{ route('rides.end', $active_ride['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl text-xs">End Ride</button>
                                </form>
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 text-center text-slate-400 font-medium text-sm">
                        No active session running at the moment.
                    </div>
                    @endif
                </div>

                <div>
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Recent Rides</h2>
                    <div class="space-y-3 mb-6">
                        @forelse ($recent_rides as $ride)
                        <div class="bg-white border border-slate-100 p-3 rounded-2xl shadow-sm flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ $ride['name'] }}</h4>
                                <p class="text-[11px] font-semibold text-slate-400 mt-0.5">{{ $ride['date'] }} &middot; {{ $ride['status'] }}</p>
                            </div>
                            <span class="text-sm font-extrabold text-slate-800">Rs. {{ number_format($ride['cost'], 2) }}</span>
                        </div>
                        @empty
                        <p class="text-sm text-slate-400">No recent rides yet.</p>
                        @endforelse
                    </div>
                    <div class="bg-emerald-950 text-white p-5 rounded-3xl">
                        <h3 class="text-base font-extrabold mt-1">Unlock Unlimited Weekends</h3>
                        <p class="text-xs text-slate-400 font-medium mt-1">Get 20% off long-distance rentals starting Friday evening.</p>
                        <form action="{{ route('offers.claim') }}" method="POST" class="mt-4">
                            @csrf
                            <input type="hidden" name="code" value="WEEKEND20">
                            <button type="submit" class="w-full bg-white hover:bg-slate-50 text-emerald-950 font-bold py-2.5 px-4 rounded-xl text-xs">Claim Offer</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
