<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Analytics</title>
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

            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl md:text-4xl font-black text-gray-900">Analytics</h1>
                <p class="text-gray-500 mt-2 text-sm md:text-base">Performance metrics for your fleet.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 md:gap-6 mb-8 md:mb-10">
                <!-- Total Bikes -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $total_bikes }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">Total Bikes</div>
                        </div>
                        <span class="material-symbols-outlined text-emerald-600 text-2xl md:text-3xl ml-3 flex-shrink-0">motorcycle</span>
                    </div>
                </div>

                <!-- Total Bookings -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $total_bookings }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">Total Bookings</div>
                        </div>
                        <span class="material-symbols-outlined text-blue-500 text-2xl md:text-3xl ml-3 flex-shrink-0">receipt_long</span>
                    </div>
                </div>

                <!-- Unique Customers -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $total_customers }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">Unique Customers</div>
                        </div>
                        <span class="material-symbols-outlined text-purple-500 text-2xl md:text-3xl ml-3 flex-shrink-0">people</span>
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-2xl md:text-3xl font-black text-gray-900">{{ $approved_count }}</div>
                            <div class="text-sm text-gray-500 mt-2 font-medium">Approved</div>
                        </div>
                        <span class="material-symbols-outlined text-emerald-500 text-2xl md:text-3xl ml-3 flex-shrink-0">check_circle</span>
                    </div>
                </div>

                <!-- Most Rented Bike -->
                <div class="bg-emerald-900 text-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-xl md:text-2xl font-black truncate" title="{{ $most_rented }}">{{ $most_rented }}</div>
                            <div class="text-sm text-emerald-300 mt-2 font-medium">Most Rented Bike</div>
                        </div>
                        <span class="material-symbols-outlined text-emerald-300 text-2xl md:text-3xl ml-3 flex-shrink-0">star</span>
                    </div>
                </div>
            </div>

            <!-- Additional Analytics Section (Optional) -->
            @if(isset($total_bookings) && $total_bookings > 0)
            <div class="mt-8 md:mt-10">
                <div class="bg-white border border-gray-200 rounded-xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg md:text-xl font-bold text-gray-900 mb-4">Quick Insights</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <span class="material-symbols-outlined text-emerald-600 text-2xl">trending_up</span>
                            <div>
                                <p class="text-sm text-gray-500">Booking Rate</p>
                                <p class="font-bold text-gray-900">
                                    @if($total_bikes > 0)
                                        {{ number_format(($total_bookings / $total_bikes), 1) }} per bike
                                    @else
                                        0 per bike
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <span class="material-symbols-outlined text-blue-600 text-2xl">percent</span>
                            <div>
                                <p class="text-sm text-gray-500">Approval Rate</p>
                                <p class="font-bold text-gray-900">
                                    @if($total_bookings > 0)
                                        {{ number_format(($approved_count / $total_bookings) * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <span class="material-symbols-outlined text-purple-600 text-2xl">group</span>
                            <div>
                                <p class="text-sm text-gray-500">Customer Retention</p>
                                <p class="font-bold text-gray-900">
                                    @if($total_customers > 0)
                                        {{ number_format(($total_bookings / $total_customers), 1) }} bookings/customer
                                    @else
                                        0
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</body>
</html>
