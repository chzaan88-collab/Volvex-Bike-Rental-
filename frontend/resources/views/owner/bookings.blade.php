<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Owner Bookings</title>
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

            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl md:text-4xl font-black text-gray-900">Booking Requests</h1>
                <p class="text-gray-500 mt-2 text-sm md:text-base">Review and manage incoming bookings.</p>
            </div>

            <!-- Bookings List -->
            <div class="space-y-4">
                @forelse ($bookings as $booking)
                <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 lg:gap-6">
                        <!-- Left Side: Booking Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h3 class="font-bold text-lg text-gray-900 truncate">{{ $booking['bike_name'] }}</h3>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap
                                    @if($booking['status'] === 'Approved')
                                        bg-emerald-100 text-emerald-700
                                    @elseif($booking['status'] === 'Pending')
                                        bg-amber-100 text-amber-700
                                    @elseif($booking['status'] === 'Rejected')
                                        bg-red-100 text-red-700
                                    @else
                                        bg-gray-100 text-gray-600
                                    @endif
                                ">
                                    {{ $booking['status'] }}
                                </span>
                            </div>

                            <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gray-400 text-base">calendar_today</span>
                                    <span class="whitespace-nowrap">{{ $booking['start_date'] }}</span>
                                    <span class="text-gray-400">{{ $booking['start_time'] }}</span>
                                </div>
                                <span class="hidden sm:inline text-gray-300">&rarr;</span>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gray-400 text-base">event</span>
                                    <span class="whitespace-nowrap">{{ $booking['end_date'] }}</span>
                                    <span class="text-gray-400">{{ $booking['end_time'] }}</span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-gray-400 text-base">schedule</span>
                                <p class="text-xs text-gray-500 font-medium">{{ $booking['booking_type'] }} rental</p>
                            </div>
                        </div>

                        <!-- Right Side: Amount and Actions -->
                        <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end gap-3 lg:gap-4 flex-shrink-0">
                            <div class="text-xl md:text-2xl font-black text-gray-900 whitespace-nowrap">
                                Rs. {{ number_format($booking['total_amount'], 2) }}
                            </div>

                            @if ($booking['status'] === 'Pending')
                            <div class="flex gap-2 flex-wrap">
                                <form action="{{ route('owner.bookings.approve', $booking['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors duration-200 shadow-sm hover:shadow-md whitespace-nowrap">
                                        <span class="material-symbols-outlined text-lg">check</span>
                                        <span>Approve</span>
                                    </button>
                                </form>
                                <form action="{{ route('owner.bookings.reject', $booking['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors duration-200 shadow-sm hover:shadow-md whitespace-nowrap">
                                        <span class="material-symbols-outlined text-lg">close</span>
                                        <span>Reject</span>
                                    </button>
                                </form>
                            </div>
                            @endif

                            @if ($booking['status'] === 'Approved')
                            <div class="flex gap-2 flex-wrap">
                                <form action="{{ route('agreements.generate', $booking['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-2 rounded-lg text-xs font-bold transition-colors duration-200 shadow-sm hover:shadow-md whitespace-nowrap">
                                        <span class="material-symbols-outlined text-base">description</span>
                                        <span>Generate Agreement</span>
                                    </button>
                                </form>
                                <a href="{{ route('agreements.download', $booking['id']) }}" class="inline-flex items-center justify-center gap-1.5 bg-blue-700 hover:bg-blue-800 text-white px-3 py-2 rounded-lg text-xs font-bold transition-colors duration-200 shadow-sm hover:shadow-md whitespace-nowrap">
                                    <span class="material-symbols-outlined text-base">download</span>
                                    <span>Download PDF</span>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-5xl text-gray-300 block mb-4">receipt_long</span>
                    <p class="text-gray-400 font-medium text-lg">No booking requests.</p>
                    <p class="text-gray-300 text-sm mt-2">When customers book your bikes, they'll appear here.</p>
                </div>
                @endforelse
            </div>

        </div>
    </div>
</body>
</html>
