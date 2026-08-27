<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use App\Services\FastApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class MainController extends Controller
{
    public function __construct(protected FastApiClient $api)
    {
    }

    protected function accountMode(): string
    {
        return Session::get('fastapi_user.account_mode', 'rider');
    }

    protected function userName(): string
    {
        return Session::get('fastapi_user.full_name', 'Guest');
    }

    protected function sharedViewData(string $activeNav = ''): array
    {
        return [
            'activeNav' => $activeNav,
            'user_name' => $this->userName(),
            'accountMode' => $this->accountMode(),
            'rider_status' => 'ACTIVE',
        ];
    }

    protected function staticBaseUrl(): string
    {
        // FastAPI serves uploaded images from its root under /static/uploads.
        // base_url is configured as http://host:port/api/v1, so strip the
        // trailing API prefix to reach the host root that serves /static.
        $base = rtrim(config('services.fastapi.base_url', 'http://127.0.0.1:8000/api/v1'), '/');
        return preg_replace('#/api/v\d+(?:/?)$#', '', $base);
    }

    protected function resolveImageUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }
        // Absolute URLs (https://...) and placehold links are returned as-is.
        if (preg_match('#^https?://#', $image)) {
            return $image;
        }
        $base = $this->staticBaseUrl();
        // The API router stores "/static/uploads/<file>"; legacy code stored a
        // bare filename — handle both to avoid the old double "/static/uploads/"
        // path bug that broke every uploaded image.
        if (str_starts_with($image, '/static/')) {
            return $base . $image;
        }
        return $base . '/static/uploads/' . $image;
    }

    protected function monthlyRate(array $bike): float
    {
        $monthly = (float) ($bike['price_per_month'] ?? 0);
        if ($monthly > 0) {
            return $monthly;
        }
        // Fall back to a 30-day month derived from the daily rate.
        return ((float) ($bike['price_per_day'] ?? ($bike['daily_rate'] ?? 0))) * 30;
    }

    protected function normalizeBike(array $bike): object
    {
        return (object) [
            'id' => $bike['id'] ?? null,
            'name' => $bike['bike_name'] ?? ($bike['name'] ?? 'Unknown'),
            'bike_name' => $bike['bike_name'] ?? ($bike['name'] ?? 'Unknown'),
            'brand' => $bike['brand'] ?? '',
            'model' => $bike['model'] ?? '',
            'bike_type' => $bike['bike_type'] ?? '',
            'hourly_rate' => (float) ($bike['price_per_hour'] ?? ($bike['hourly_rate'] ?? 0)),
            'daily_rate' => (float) ($bike['price_per_day'] ?? ($bike['daily_rate'] ?? 0)),
            'monthly_rate' => $this->monthlyRate($bike),
            'price_per_hour' => (float) ($bike['price_per_hour'] ?? 0),
            'price_per_day' => (float) ($bike['price_per_day'] ?? 0),
            'price_per_month' => (float) ($bike['price_per_month'] ?? 0),
            'license' => $bike['registration_number'] ?? ($bike['license'] ?? 'N/A'),
            'registration_number' => $bike['registration_number'] ?? 'N/A',
            // FIXED: build a correct, single /static/uploads/ URL.
            'image_url' => $this->resolveImageUrl($bike['image'] ?? null),
            'image' => $bike['image'] ?? null,
            'city' => $bike['city'] ?? '',
            'last_known_address' => $bike['city'] ?? 'Location unavailable',
            'engine_cc' => $bike['engine_cc'] ?? '',
            'fuel_type' => $bike['fuel_type'] ?? '',
            'transmission' => $bike['transmission'] ?? '',
            'description' => $bike['description'] ?? '',
            'status' => $bike['status'] ?? 'available',
            'battery' => $bike['battery'] ?? '—',
        ];
    }

    protected function enrichBooking(array $booking): array
    {
        return [
            'id' => $booking['id'],
            'bike_id' => $booking['bike_id'],
            'bike_name' => $booking['bike_name'] ?? 'Motorbike',
            'name' => $booking['bike_name'] ?? 'Motorbike',
            'model' => $booking['bike_model'] ?? '',
            'license' => $booking['registration_number'] ?? 'N/A',
            'city' => $booking['city'] ?? '',
            'address' => $booking['city'] ?? 'Location unavailable',
            'status' => $booking['status'],
            'booking_type' => $booking['booking_type'],
            'start_date' => $booking['start_date'],
            'end_date' => $booking['end_date'],
            'start_time' => $booking['start_time'],
            'end_time' => $booking['end_time'],
            'due_time' => ($booking['end_date'] ?? '') . ' ' . ($booking['end_time'] ?? ''),
            'total_amount' => (float) ($booking['total_amount'] ?? 0),
            'cost' => (float) ($booking['total_amount'] ?? 0),
            'base_amount' => (float) ($booking['base_amount'] ?? 0),
            'discount_amount' => (float) ($booking['discount_amount'] ?? 0),
            'time_multiplier' => (float) ($booking['time_multiplier'] ?? 1),
            'discount_code' => $booking['discount_code'] ?? '',
            'date' => $booking['start_date'] ?? '',
            'duration' => ($booking['booking_type'] ?? 'Ride') . ' rental',
            'battery' => '—',
        ];
    }

    protected function filterAndSortBikes(Collection $bikes, Request $request): Collection
    {
        $search = strtolower($request->query('q', ''));
        if ($search !== '') {
            $bikes = $bikes->filter(function ($bike) use ($search) {
                $haystack = strtolower(implode(' ', [
                    $bike->name,
                    $bike->model,
                    $bike->city,
                    $bike->bike_type,
                ]));
                return str_contains($haystack, $search);
            });
        }

        return match ($request->query('sort')) {
            'price_low' => $bikes->sortBy('hourly_rate')->values(),
            'price_high' => $bikes->sortByDesc('hourly_rate')->values(),
            default => $bikes->sortBy('name')->values(),
        };
    }

    public function dashboard()
    {
        if ($this->accountMode() === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        if (! Session::has('fastapi_token')) {
            return view('dashboard', array_merge($this->sharedViewData('dashboard'), [
                'active_rides_count' => 0,
                'current_balance' => 0.0,
                'lifetime_spend' => 0.0,
                'active_ride' => null,
                'recent_rides' => [],
            ]));
        }

        $user = $this->api->me()->json() ?? [];
        Session::put('fastapi_user', $user);
        $bookings = collect($this->api->myBookings()->json() ?? [])->map(fn ($b) => $this->enrichBooking($b));

        $activeBookings = $bookings->whereIn('status', ['Pending', 'Approved']);
        $pastBookings = $bookings->whereIn('status', ['Rejected', 'Completed']);

        // Location-based bike recommendations: bikes in the user's city
        // are ranked first so the dashboard always suggests nearby rides.
        $userLocation = $user['location'] ?? 'Karachi';
        $recommendedBikes = collect([]);
        try {
            $recommendedBikes = collect($this->api->recommendBikes($userLocation)->json() ?? [])
                ->map(fn ($b) => $this->normalizeBike($b));
        } catch (FastApiException $e) {
            // Recommendations are non-fatal; dashboard still works.
        }

        return view('dashboard', array_merge($this->sharedViewData('dashboard'), [
            'active_rides_count' => $activeBookings->count(),
            'current_balance' => (float) ($user['wallet_balance'] ?? 0),
            'lifetime_spend' => (float) $bookings->sum('total_amount'),
            'active_ride' => $activeBookings->first(),
            'recent_rides' => $pastBookings->take(5)->values()->all(),
            'recommended_bikes' => $recommendedBikes,
            'user_location' => $userLocation,
        ]));
    }

    public function catalog(Request $request)
    {
        if ($this->accountMode() === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        $filters = array_filter([
            'city' => $request->query('city'),
            'bike_type' => $request->query('model'),
        ]);

        $rawBikes = $this->api->listBikes($filters)->json() ?? [];
        $bikes = $this->filterAndSortBikes(
            collect($rawBikes)->map(fn ($b) => $this->normalizeBike($b)),
            $request
        );

        $favoriteBikeIds = [];
        if (Session::has('fastapi_token')) {
            try {
                $favoriteBikeIds = collect($this->api->favorites()->json() ?? [])->pluck('id')->all();
            } catch (FastApiException $e) {
                // Ignore favorites error; catalog still works
            }
        }

        return view('catalog', array_merge($this->sharedViewData('catalog'), [
            'bikes' => $bikes,
            'favorite_bike_ids' => $favoriteBikeIds,
            'available_models' => $bikes->pluck('bike_type')->filter()->unique()->values(),
            'search_term' => $request->query('q', ''),
            'sort_by' => $request->query('sort', ''),
            'model_filter' => $request->query('model', ''),
        ]));
    }

    public function bookRide(Request $request, int $bike)
    {
        $validated = $request->validate([
            'booking_type' => 'required|in:Hourly,Daily,Monthly',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'offer_code' => 'nullable|string',
        ]);

        // Update user profile with phone / CNIC from booking form
        $phone = $request->input('phone');
        $cnic = $request->input('cnic');

        if ($phone || $cnic) {
            try {
                $user = $this->api->updateProfile([
                    'phone' => $phone ?: null,
                    'cnic' => $cnic ?: null,
                ])->json();
                Session::put('fastapi_user', array_merge(Session::get('fastapi_user', []), $user));
            } catch (FastApiException $e) {
                // Non-fatal — booking can still proceed
            }
        }

        try {
            $this->api->createBooking($bike, $validated);
        } catch (FastApiException $e) {
            return back()->withErrors(['ride' => $e->getMessage()])->withInput();
        }

        return redirect()->route('dashboard')->with('status', 'Booking request sent — waiting on owner approval.');
    }

    public function quoteBooking(Request $request, int $bike)
    {
        if (! Session::has('fastapi_token')) {
            return response()->json(['detail' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'booking_type' => 'required|in:Hourly,Daily,Monthly',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'offer_code' => 'nullable|string',
        ]);

        try {
            return response()->json($this->api->quoteBooking($bike, $validated)->json());
        } catch (FastApiException $e) {
            return response()->json(['detail' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function booking(Request $request)
    {
        if ($this->accountMode() === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        $bikeId = $request->query('bike');

        if ($bikeId) {
            try {
                $bike = $this->normalizeBike($this->api->getBike((int) $bikeId)->json() ?? []);
            } catch (FastApiException $e) {
                return redirect()->route('catalog.index')->withErrors(['ride' => $e->getMessage()]);
            }

            // Load the user's claimed offers so they can pick a discount at checkout.
            $offers = collect([]);
            if (Session::has('fastapi_token')) {
                try {
                    $offers = collect($this->api->listOffers()->json() ?? [])
                        ->filter(fn ($o) => !empty($o['claimed']));
                } catch (FastApiException $e) {
                    // Ignore offers error; checkout still works.
                }
            }

            return view('booking', array_merge($this->sharedViewData('catalog'), [
                'bike' => $bike,
                'rides' => collect([]),
                'checkout' => true,
                'offers' => $offers,
            ]));
        }

        $rawBookings = Session::has('fastapi_token') ? ($this->api->myBookings()->json() ?? []) : [];
        $rides = collect($rawBookings)->map(fn ($b) => $this->enrichBooking($b));

        return view('booking', array_merge($this->sharedViewData('active-rides'), [
            'bike' => null,
            'rides' => $rides,
            'checkout' => false,
        ]));
    }

    public function wallet()
    {
        if ($this->accountMode() === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        if (! Session::has('fastapi_token')) {
            return view('wallet', array_merge($this->sharedViewData('wallet'), [
                'current_balance' => 0.0,
                'lifetime_spend' => 0.0,
                'transactions' => collect([]),
            ]));
        }

        $user = $this->api->me()->json() ?? [];
        $rawBookings = $this->api->myBookings()->json() ?? [];
        $bookings = collect($rawBookings);
        $transactions = collect($this->api->walletTransactions()->json() ?? []);

        return view('wallet', array_merge($this->sharedViewData('wallet'), [
            'current_balance' => (float) ($user['wallet_balance'] ?? 0),
            'lifetime_spend' => (float) $bookings->sum('total_amount'),
            'transactions' => $transactions,
        ]));
    }

    public function walletTopup(Request $request)
    {
        $validated = $request->validate(['amount' => 'required|numeric|min:1']);

        try {
            $user = $this->api->walletTopup((float) $validated['amount'])->json();
            Session::put('fastapi_user', $user);
        } catch (FastApiException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Wallet topped up.');
    }

    public function settings()
    {
        $userData = $this->api->me()->json() ?? [];
        Session::put('fastapi_user', $userData);

        $user = (object) [
            'name' => $userData['full_name'] ?? '',
            'full_name' => $userData['full_name'] ?? '',
            'email' => $userData['email'] ?? '',
            'phone' => $userData['phone'] ?? '',
            'cnic' => $userData['cnic'] ?? '',
            'location' => $userData['location'] ?? 'Karachi',
            'account_mode' => strtoupper($userData['account_mode'] ?? 'rider'),
            'rider_status' => 'ACTIVE',
            'current_balance' => (float) ($userData['wallet_balance'] ?? 0),
            'wallet_balance' => (float) ($userData['wallet_balance'] ?? 0),
            'created_at' => null,
        ];

        return view('settings', array_merge($this->sharedViewData('settings'), [
            'user' => $user,
        ]));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'cnic' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:100',
        ]);

        try {
            $user = $this->api->updateProfile([
                'full_name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'cnic' => $validated['cnic'] ?? null,
                'location' => $validated['location'] ?? null,
            ])->json();
            Session::put('fastapi_user', $user);
        } catch (FastApiException $e) {
            return back()->withErrors(['name' => $e->getMessage()]);
        }

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->api->updatePassword($validated['current_password'], $validated['password']);
        } catch (FastApiException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()]);
        }

        return back()->with('status', 'Password changed successfully.');
    }

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $data = $this->api->login($credentials['email'], $credentials['password'])->json();
        } catch (FastApiException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->onlyInput('email');
        }

        Session::put('fastapi_token', $data['access_token'] ?? null);
        Session::put('fastapi_user', $data['user'] ?? []);
        $request->session()->regenerate();

        $mode = $data['user']['account_mode'] ?? 'rider';

        return redirect()->intended($mode === 'owner' ? route('owner.dashboard') : route('dashboard'));
    }

    public function logout(Request $request)
    {
        Session::forget(['fastapi_token', 'fastapi_user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }

    public function showRegister()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:20',
            'cnic' => 'required|string|max:20',
            'location' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $data = $this->api->register([
                'full_name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'cnic' => $validated['cnic'],
                'location' => $validated['location'],
                'password' => $validated['password'],
                'role' => 'customer',
            ])->json();
        } catch (FastApiException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->onlyInput('name', 'email', 'phone', 'cnic', 'location');
        }

        Session::put('fastapi_token', $data['access_token'] ?? null);
        Session::put('fastapi_user', $data['user'] ?? []);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function switchMode()
    {
        try {
            $user = $this->api->switchMode()->json();
            Session::put('fastapi_user', $user);
        } catch (FastApiException $e) {
            return back()->withErrors(['mode' => $e->getMessage()]);
        }

        $mode = $user['account_mode'] ?? 'rider';

        return redirect()->route($mode === 'owner' ? 'owner.dashboard' : 'dashboard')
            ->with('status', 'Switched to ' . $mode . ' mode.');
    }

    public function favorites()
    {
        if ($this->accountMode() === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        if (! Session::has('fastapi_token')) {
            return redirect()->route('login');
        }

        $rawFavorites = $this->api->favorites()->json() ?? [];
        $bikes = collect($rawFavorites)->map(fn ($b) => $this->normalizeBike($b));

        return view('favorites', array_merge($this->sharedViewData('favorites'), [
            'bikes' => $bikes,
        ]));
    }

    public function toggleFavorite(int $bike)
    {
        if (! Session::has('fastapi_token')) {
            return redirect()->route('login');
        }

        try {
            $favorites = collect($this->api->favorites()->json() ?? []);
            $exists = $favorites->contains('id', $bike);

            if ($exists) {
                $this->api->removeFavorite($bike);
                $message = 'Bike removed from favorites.';
            } else {
                $this->api->addFavorite($bike);
                $message = 'Bike added to favorites!';
            }
        } catch (FastApiException $e) {
            return back()->withErrors(['favorite' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }

    public function extendRide(Request $request, int $ride)
    {
        try {
            $this->api->extendBooking($ride, (int) $request->input('extra_hours', 1));
        } catch (FastApiException $e) {
            return back()->withErrors(['ride' => $e->getMessage()]);
        }

        return back()->with('status', 'Ride extended successfully.');
    }

    public function endRide(int $ride)
    {
        try {
            $this->api->completeBooking($ride);
        } catch (FastApiException $e) {
            return back()->withErrors(['ride' => $e->getMessage()]);
        }

        return back()->with('status', 'Ride completed successfully.');
    }

    public function notifications()
    {
        if ($this->accountMode() === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        if (! Session::has('fastapi_token')) {
            return redirect()->route('login');
        }

        $bookings = collect($this->api->myBookings()->json() ?? []);

        $notifs = $bookings->map(function ($b) {
            $status = $b['status'] ?? 'Pending';
            $bike = $b['bike_name'] ?? 'Bike';
            $class = 'primary';
            $time = 'Recent';

            if ($status === 'Approved') {
                $title = 'Booking Approved';
                $message = "Your booking for {$bike} has been approved. Please review and sign the agreement.";
                $class = 'success';
            } elseif ($status === 'Pending') {
                $title = 'Booking Pending';
                $message = "Your booking request for {$bike} is currently pending owner review.";
                $class = 'warning';
            } elseif ($status === 'Rejected') {
                $title = 'Booking Rejected';
                $message = "Unfortunately your booking for {$bike} was rejected by the owner.";
                $class = 'danger';
            } elseif ($status === 'Completed') {
                $title = 'Ride Completed';
                $message = "Your ride for {$bike} has been completed. Please leave a review.";
                $class = 'info';
            } else {
                $title = 'Booking Update';
                $message = "Your booking for {$bike} is currently {$status}.";
                $class = 'primary';
            }

            return [
                'title' => $title,
                'message' => $message,
                'class' => $class,
                'time' => $time,
                'booking_id' => $b['id'] ?? 0,
            ];
        });

        if ($notifs->isEmpty()) {
            $notifs = collect([[
                'title' => 'Welcome to Velex',
                'message' => 'Explore available bikes and start booking your rides today!',
                'class' => 'primary',
                'time' => 'Just now',
                'booking_id' => 0,
            ]]);
        }

        return view('notifications', array_merge($this->sharedViewData('notifications'), [
            'notifications' => $notifs,
            'rides' => $bookings->take(5)->map(fn ($b) => $this->enrichBooking($b))->values(),
        ]));
    }

    // --- Reviews ---

    public function reviews()
    {
        if (! Session::has('fastapi_token')) {
            return redirect()->route('login');
        }

        $reviews = collect($this->api->myReviews()->json() ?? []);
        $bookings = collect($this->api->myBookings()->json() ?? []);
        $completedBookings = $bookings->where('status', 'Completed');

        // Filter out bookings that already have reviews
        $reviewedBookingIds = $reviews->pluck('booking_id')->all();
        $reviewableBookings = $completedBookings->filter(function ($b) use ($reviewedBookingIds) {
            return ! in_array($b['id'], $reviewedBookingIds);
        });

        return view('reviews', array_merge($this->sharedViewData('reviews'), [
            'reviews' => $reviews,
            'reviewable_bookings' => $reviewableBookings->values(),
        ]));
    }

    public function submitReview(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer',
            'bike_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:1000',
        ]);

        try {
            $this->api->createReview($validated);
        } catch (FastApiException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return redirect()->route('reviews.index')->with('status', 'Thank you for your review!');
    }

    // --- Owner ---

    public function ownerEarnings()
    {
        if ($this->accountMode() !== 'owner') {
            return redirect()->route('dashboard');
        }

        $bookings = collect($this->api->ownerBookings()->json() ?? []);
        $approved = $bookings->where('status', 'Approved');
        $pending = $bookings->where('status', 'Pending');
        $completed = $bookings->where('status', 'Completed');
        $totalEarnings = (float) $approved->sum('total_amount');

        return view('owner.earnings', array_merge($this->sharedViewData('owner-earnings'), [
            'total_earnings' => $totalEarnings,
            'approved_count' => $approved->count(),
            'pending_count' => $pending->count(),
            'completed_count' => $completed->count(),
            'total_bookings' => $bookings->count(),
            'bookings' => $bookings->map(fn ($b) => $this->enrichBooking($b))->values(),
        ]));
    }

    public function ownerAnalytics()
    {
        if ($this->accountMode() !== 'owner') {
            return redirect()->route('dashboard');
        }

        $bikes = collect($this->api->myBikes()->json() ?? []);
        $bookings = collect($this->api->ownerBookings()->json() ?? []);

        // Most rented bike
        $mostRented = 'None';
        if ($bookings->isNotEmpty()) {
            $counts = $bookings->groupBy('bike_id')->map->count();
            $topBikeId = $counts->sortDesc()->keys()->first();
            $topBike = $bikes->firstWhere('id', $topBikeId);
            if ($topBike) {
                $mostRented = $topBike['bike_name'] ?? 'Unknown';
            }
        }

        return view('owner.analytics', array_merge($this->sharedViewData('owner-analytics'), [
            'total_bikes' => $bikes->count(),
            'total_bookings' => $bookings->count(),
            'total_customers' => $bookings->pluck('customer_id')->unique()->count(),
            'most_rented' => $mostRented,
            'approved_count' => $bookings->where('status', 'Approved')->count(),
        ]));
    }

    public function claimOffer(Request $request)
    {
        $code = $request->input('code', 'WEEKEND20');

        try {
            $result = $this->api->claimOfferByCode($code)->json();
            $message = $result['message'] ?? 'Offer claimed successfully!';
        } catch (FastApiException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }

    // --- Owner ---

    public function ownerDashboard()
    {
        if ($this->accountMode() !== 'owner') {
            return redirect()->route('dashboard');
        }

        $bikes = collect($this->api->myBikes()->json() ?? []);
        $bookings = collect($this->api->ownerBookings()->json() ?? []);
        $pending = $bookings->where('status', 'Pending')->count();
        $approved = $bookings->where('status', 'Approved')->count();
        $earnings = (float) $bookings->where('status', 'Approved')->sum('total_amount');

        return view('owner.dashboard', array_merge($this->sharedViewData('owner-dashboard'), [
            'bike_count' => $bikes->count(),
            'pending_count' => $pending,
            'approved_count' => $approved,
            'earnings' => $earnings,
            'recent_bookings' => $bookings->take(5)->map(fn ($b) => $this->enrichBooking($b))->values(),
        ]));
    }

    public function ownerBikes()
    {
        if ($this->accountMode() !== 'owner') {
            return redirect()->route('dashboard');
        }

        $bikes = collect($this->api->myBikes()->json() ?? [])->map(fn ($b) => $this->normalizeBike($b));

        return view('owner.bikes', array_merge($this->sharedViewData('owner-bikes'), [
            'bikes' => $bikes,
        ]));
    }

    public function ownerBikeCreateForm()
    {
        if ($this->accountMode() !== 'owner') {
            return redirect()->route('dashboard');
        }

        return view('owner.bike-form', $this->sharedViewData('owner-bikes'));
    }

    public function ownerBikeCreate(Request $request)
    {
        $validated = $request->validate([
            'bike_name' => 'required|string|max:255',
            'brand' => 'required|string|max:100',
            'brand_other' => 'nullable|string|max:100|required_if:brand,Other',
            'model' => 'required|string|max:100',
            'bike_type' => 'required|string|max:100',
            'registration_number' => 'required|string|max:50',
            'color' => 'required|string|max:50',
            'color_other' => 'nullable|string|max:50|required_if:color,Other',
            'city' => 'required|string|max:100',
            'city_other' => 'nullable|string|max:100|required_if:city,Other',
            'price_per_hour' => 'required|numeric|min:0',
            'price_per_day' => 'required|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'engine_cc' => 'required|string|max:20',
            'fuel_type' => 'required|string|max:50',
            'transmission' => 'required|string|max:50',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // When "Other" is selected, use the custom value the user typed.
        $brand = $validated['brand'] === 'Other' ? $validated['brand_other'] : $validated['brand'];
        $color = $validated['color'] === 'Other' ? $validated['color_other'] : $validated['color'];
        $city = $validated['city'] === 'Other' ? $validated['city_other'] : $validated['city'];

        $formData = [
            'bike_name' => $validated['bike_name'],
            'brand' => $brand,
            'model' => $validated['model'],
            'bike_type' => $validated['bike_type'],
            'registration_number' => $validated['registration_number'],
            'color' => $color,
            'city' => $city,
            'price_per_hour' => $validated['price_per_hour'],
            'price_per_day' => $validated['price_per_day'],
            'price_per_month' => $validated['price_per_month'] ?? 0,
            'engine_cc' => $validated['engine_cc'],
            'fuel_type' => $validated['fuel_type'],
            'transmission' => $validated['transmission'],
            'description' => $validated['description'] ?? '',
            'gps' => 'Yes',
            'helmet' => 'Included',
        ];

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->getRealPath();
        }

        try {
            $this->api->createBikeWithImage($formData, $imagePath);
        } catch (FastApiException $e) {
            return back()->withErrors(['bike_name' => $e->getMessage()])->withInput();
        }

        return redirect()->route('owner.bikes')->with('status', 'Bike added successfully.');
    }

    public function ownerBookings()
    {
        if ($this->accountMode() !== 'owner') {
            return redirect()->route('dashboard');
        }

        $bookings = collect($this->api->ownerBookings()->json() ?? [])->map(fn ($b) => $this->enrichBooking($b));

        return view('owner.bookings', array_merge($this->sharedViewData('owner-bookings'), [
            'bookings' => $bookings,
        ]));
    }

    public function ownerApproveBooking(int $booking)
    {
        try {
            $this->api->approveBooking($booking);
        } catch (FastApiException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking approved.');
    }

    public function ownerRejectBooking(int $booking)
    {
        try {
            $this->api->rejectBooking($booking);
        } catch (FastApiException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking rejected.');
    }

    public function ownerDeleteBike(int $bike)
    {
        try {
            $this->api->deleteBike($bike);
        } catch (FastApiException $e) {
            return back()->withErrors(['bike' => $e->getMessage()]);
        }

        return back()->with('status', 'Bike deleted.');
    }

    // --- Agreements ---

    public function generateAgreement(int $booking)
    {
        try {
            $result = $this->api->generateAgreement($booking)->json();
            $message = 'Agreement PDF generated successfully.';
            if (! empty($result['download_url'])) {
                return redirect()->away(rtrim(config('services.fastapi.base_url'), '/') . $result['download_url'])
                    ->with('status', $message);
            }
        } catch (FastApiException $e) {
            return back()->withErrors(['agreement' => $e->getMessage()]);
        }

        return back()->with('status', $message ?? 'Agreement processed.');
    }

    public function downloadAgreement(int $booking)
    {
        try {
            $response = $this->api->downloadAgreement($booking);

            return response()->streamDownload(function () use ($response) {
                echo $response->body();
            }, "Agreement_{$booking}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (FastApiException $e) {
            return back()->withErrors(['agreement' => $e->getMessage()]);
        }
    }

    public function agreementStatus(int $booking)
    {
        try {
            return response()->json($this->api->agreementStatus($booking)->json());
        } catch (FastApiException $e) {
            return response()->json(['detail' => $e->getMessage()], 404);
        }
    }

    // --- Admin ---

    protected function requireAdmin()
    {
        $user = Session::get('fastapi_user', []);
        if (($user['role'] ?? '') !== 'admin') {
            abort(404);
        }
    }

    public function adminDashboard()
    {
        $this->requireAdmin();
        $bikes = collect($this->api->listBikes()->json() ?? []);
        $bookings = collect($this->api->myBookings()->json() ?? []);

        return view('admin.dashboard', array_merge($this->sharedViewData('admin-dashboard'), [
            'total_bikes' => $bikes->count(),
            'total_bookings' => $bookings->count(),
            'total_earnings' => (float) $bookings->where('status', 'Approved')->sum('total_amount'),
            'recent_bookings' => $bookings->take(5)->map(fn ($b) => $this->enrichBooking($b))->values(),
        ]));
    }

    public function adminBikes()
    {
        $this->requireAdmin();
        $bikes = collect($this->api->listBikes()->json() ?? [])->map(fn ($b) => $this->normalizeBike($b));

        return view('admin.bikes', array_merge($this->sharedViewData('admin-bikes'), [
            'bikes' => $bikes,
        ]));
    }

    public function adminBookings()
    {
        $this->requireAdmin();
        $bookings = collect($this->api->myBookings()->json() ?? [])->map(fn ($b) => $this->enrichBooking($b));

        return view('admin.bookings', array_merge($this->sharedViewData('admin-bookings'), [
            'bookings' => $bookings,
        ]));
    }

    // --- AI Features ---

    public function aiChat(Request $request)
    {
        $validated = $request->validate(['message' => 'required|string|max:2000']);

        try {
            $result = $this->api->aiChat($validated['message'])->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiRecommend(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'required|numeric|min:0',
            'city' => 'required|string',
            'category' => 'required|string',
            'ride_type' => 'required|string',
        ]);

        try {
            $result = $this->api->aiRecommendBikes($validated)->json();
        } catch (FastApiException $e) {
            return back()->withErrors(['ai' => $e->getMessage()]);
        }

        return back()->with('ai_recommendations', $result['recommendations'] ?? []);
    }

    public function aiPricePrediction(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string',
            'engine_cc' => 'required|integer',
            'bike_type' => 'required|string',
            'gps' => 'required|string',
            'helmet' => 'required|string',
            'city' => 'required|string',
        ]);

        try {
            $result = $this->api->aiPredictPrice($validated)->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiDemandForecast(Request $request)
    {
        $validated = $request->validate([
            'city' => 'required|string',
            'weather' => 'required|string',
            'day' => 'required|string',
            'month' => 'required|string',
        ]);

        try {
            $result = $this->api->aiForecastDemand($validated)->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiFraudDetection(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'bike_id' => 'required|integer',
            'booking_amount' => 'required|numeric',
        ]);

        try {
            $result = $this->api->aiDetectFraud($validated)->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiMaintenancePrediction(Request $request)
    {
        $validated = $request->validate(['bike_id' => 'required|integer']);

        try {
            $result = $this->api->aiPredictMaintenance((int) $validated['bike_id'])->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiReviewAnalysis(Request $request)
    {
        $validated = $request->validate(['review' => 'required|string|max:2000']);

        try {
            $result = $this->api->aiAnalyzeReview($validated['review'])->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiAgreementAnalysis(Request $request)
    {
        $validated = $request->validate(['agreement' => 'required|string']);

        try {
            $result = $this->api->aiAnalyzeAgreement($validated['agreement'])->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function aiSemanticSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
            'top_k' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $result = $this->api->aiSemanticSearch($validated['query'], (int) ($validated['top_k'] ?? 5))->json();
        } catch (FastApiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }
}
