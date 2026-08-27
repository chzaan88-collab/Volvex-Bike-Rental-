<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Owner Dashboard</title>
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
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900">Owner Dashboard</h1>
                    <p class="text-gray-500 mt-2 text-sm md:text-base">Manage your fleet and bookings.</p>
                </div>
                <a href="{{ route('owner.bikes.create') }}"
                   class="inline-flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white px-5 py-2.5 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md whitespace-nowrap w-full md:w-auto">
                    <span class="material-symbols-outlined text-lg">add</span>
                    <span>Add Bike</span>
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 md:mb-10">
                <!-- My Bikes -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $bike_count }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">My Bikes</div>
                        </div>
                        <span class="material-symbols-outlined text-emerald-600 text-3xl ml-3 flex-shrink-0">motorcycle</span>
                    </div>
                </div>

                <!-- Pending -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $pending_count }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">Pending</div>
                        </div>
                        <span class="material-symbols-outlined text-amber-500 text-3xl ml-3 flex-shrink-0">schedule</span>
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $approved_count }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">Approved</div>
                        </div>
                        <span class="material-symbols-outlined text-blue-500 text-3xl ml-3 flex-shrink-0">check_circle</span>
                    </div>
                </div>

                <!-- Earnings -->
                <div class="bg-emerald-900 text-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black truncate">Rs. {{ number_format($earnings, 2) }}</div>
                            <div class="text-sm text-emerald-300 mt-2 font-medium">Earnings</div>
                        </div>
                        <span class="material-symbols-outlined text-emerald-300 text-3xl ml-3 flex-shrink-0">payments</span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap gap-3 mb-8 md:mb-10">
                <a href="{{ route('owner.bikes') }}"
                   class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-4 py-2.5 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-lg">directions_bike</span>
                    <span>Manage Bikes</span>
                </a>
                <a href="{{ route('owner.bookings') }}"
                   class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 px-4 py-2.5 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-lg">receipt_long</span>
                    <span>View Bookings</span>
                </a>
                <a href="{{ route('owner.earnings') }}"
                   class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 px-4 py-2.5 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-lg">payments</span>
                    <span>Earnings</span>
                </a>
                <a href="{{ route('owner.analytics') }}"
                   class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 px-4 py-2.5 rounded-lg font-bold text-sm transition-colors duration-200 shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-lg">insights</span>
                    <span>Analytics</span>
                </a>
            </div>

            <!-- Recent Bookings Section -->
            <div class="mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900">Recent Bookings</h2>
            </div>

            <!-- Bookings List -->
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                @forelse ($recent_bookings as $booking)
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors duration-150 gap-3 sm:gap-4">
                    <!-- Left Side: Bike Info -->
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-gray-900 text-sm md:text-base truncate">{{ $booking['bike_name'] }}</div>
                        <div class="text-sm text-gray-500 mt-1 flex items-center flex-wrap gap-2">
                            <span class="whitespace-nowrap">{{ $booking['start_date'] }}</span>
                            <span class="hidden sm:inline text-gray-300">&middot;</span>
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap
                                @if($booking['status'] === 'Approved')
                                    bg-emerald-100 text-emerald-700
                                @elseif($booking['status'] === 'Pending')
                                    bg-amber-100 text-amber-700
                                @else
                                    bg-gray-100 text-gray-600
                                @endif
                            ">
                                {{ $booking['status'] }}
                            </span>
                        </div>
                    </div>
                    <!-- Right Side: Amount -->
                    <div class="font-bold text-gray-900 text-base md:text-lg sm:text-right flex-shrink-0 whitespace-nowrap">
                        Rs. {{ number_format($booking['total_amount'], 2) }}
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <span class="material-symbols-outlined text-gray-300 text-5xl mb-3 block">inbox</span>
                    <p class="text-gray-400 font-medium">No bookings yet.</p>
                </div>
                @endforelse
            </div>

        </div>
    </div>
</body>
</html>
