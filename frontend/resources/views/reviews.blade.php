<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Reviews</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <main class="flex-1 p-4 md:p-8 lg:p-10 max-w-5xl mx-auto w-full">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
            @endif

            <header class="mb-10">
                <h1 class="text-4xl font-black">Reviews & Ratings</h1>
                <p class="text-gray-500 mt-2">Share your experience after a completed ride.</p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h2 class="text-lg font-bold mb-4">Rate a Completed Ride</h2>
                    @if ($reviewable_bookings->isEmpty())
                    <div class="bg-white border border-gray-100 rounded-xl p-8 text-center text-gray-400 text-sm">
                        No rides available to review yet.
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach ($reviewable_bookings as $booking)
                        <div class="bg-white border border-gray-100 rounded-xl p-5">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold">{{ $booking['bike_name'] ?? 'Motorbike' }}</h3>
                                    <p class="text-xs text-gray-500">{{ $booking['start_date'] ?? '' }} &middot; {{ $booking['status'] ?? '' }}</p>
                                </div>
                                <span class="text-sm font-black">Rs. {{ number_format($booking['total_amount'] ?? 0, 2) }}</span>
                            </div>
                            <form action="{{ route('reviews.store') }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="booking_id" value="{{ $booking['id'] }}">
                                <input type="hidden" name="bike_id" value="{{ $booking['bike_id'] }}">
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">Rating</label>
                                    <div class="flex gap-2 mt-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating" value="{{ $i }}" class="hidden peer" required>
                                            <span class="material-symbols-outlined text-3xl text-gray-300 peer-checked:text-amber-400">star</span>
                                        </label>
                                        @endfor
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase">Your Review</label>
                                    <textarea name="review" rows="3" required placeholder="How was your ride?" class="w-full mt-1 border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-emerald-500"></textarea>
                                </div>
                                <button type="submit" class="bg-emerald-700 text-white px-5 py-2 rounded-lg font-bold text-sm">Submit Review</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div>
                    <h2 class="text-lg font-bold mb-4">My Reviews</h2>
                    @if ($reviews->isEmpty())
                    <div class="bg-white border border-gray-100 rounded-xl p-8 text-center text-gray-400 text-sm">
                        You haven't written any reviews yet.
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach ($reviews as $review)
                        <div class="bg-white border border-gray-100 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                <span class="material-symbols-outlined text-lg {{ $i <= ($review['rating'] ?? 0) ? 'text-amber-400' : 'text-gray-300' }}">star</span>
                                @endfor
                            </div>
                            <p class="text-sm text-gray-600">{{ $review['review'] ?? '' }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ $review['created_at'] ?? '' }}</p>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
