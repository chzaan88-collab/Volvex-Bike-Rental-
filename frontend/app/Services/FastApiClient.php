<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class FastApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $candidates = [
            config('services.fastapi.base_url'),
            env('API_BASE_URL'),
            env('VITE_API_BASE_URL'),
        ];

        $resolved = null;

        foreach ($candidates as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : $candidate;

            if (!empty($candidate) && $this->hasSchemeAndHost($candidate)) {
                $resolved = $candidate;
                break;
            }
        }

        // Fallback if nothing valid was found (missing, empty, or no scheme/host)
        if (!$resolved) {
            $resolved = 'https://volvex-bike-rental.onrender.com/api/v1';

            Log::warning('FastApiClient: falling back to default base_url. Check services.fastapi.base_url / API_BASE_URL / VITE_API_BASE_URL env values.');
        }

        $this->baseUrl = rtrim($resolved, '/');
    }

    /**
     * Validate that a URL string has both a scheme (http/https) and a host.
     */
    private function hasSchemeAndHost(string $url): bool
    {
        $parts = parse_url($url);

        return isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }

    private function getFullUrl(string $endpoint): string
    {
        $endpoint = '/' . ltrim($endpoint, '/');
        return $this->baseUrl . $endpoint;
    }

    private function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($token = Session::get('fastapi_token')) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Base HTTP client with a longer timeout + retries to survive
     * Render free-tier cold starts (backend can take 30-60s to wake up).
     */
    private function client()
    {
        return Http::withHeaders($this->getHeaders())
            ->timeout(60)
            ->connectTimeout(60)
            ->retry(2, 3000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            });
    }

    /**
     * Throws FastApiException on any non-2xx response so controller
     * try/catch(FastApiException) blocks actually work.
     */
    private function handle(Response $response): Response
    {
        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();

            if (is_array($detail)) {
                // FastAPI validation errors come back as a list of {msg, loc, ...}
                $detail = collect($detail)
                    ->map(fn ($d) => is_array($d) ? ($d['msg'] ?? json_encode($d)) : $d)
                    ->implode(', ');
            }

            throw new FastApiException($detail ?: 'Request failed', $response->status());
        }

        return $response;
    }

    // ---------------------------------------------------------------
    // Generic HTTP verbs
    // ---------------------------------------------------------------

    public function post(string $endpoint, array $data = []): Response
    {
        return $this->handle($this->client()->post($this->getFullUrl($endpoint), $data));
    }

    public function get(string $endpoint, array $query = []): Response
    {
        return $this->handle($this->client()->get($this->getFullUrl($endpoint), $query));
    }

    public function put(string $endpoint, array $data = []): Response
    {
        return $this->handle($this->client()->put($this->getFullUrl($endpoint), $data));
    }

    public function patch(string $endpoint, array $data = []): Response
    {
        return $this->handle($this->client()->patch($this->getFullUrl($endpoint), $data));
    }

    public function delete(string $endpoint, array $data = []): Response
    {
        return $this->handle($this->client()->delete($this->getFullUrl($endpoint), $data));
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function register(array $data): Response
    {
        return $this->post('/auth/register', $data);
    }

    public function login(string $email, string $password): Response
    {
        return $this->post('/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function me(): Response
    {
        return $this->get('/auth/me');
    }

    // ---------------------------------------------------------------
    // Bikes
    // ---------------------------------------------------------------

    public function listBikes(array $filters = []): Response
    {
        return $this->get('/bikes', $filters);
    }

    public function myBikes(): Response
    {
        return $this->get('/bikes/mine');
    }

    public function recommendBikes(string $location): Response
    {
        return $this->get('/bikes/recommendations', ['location' => $location]);
    }

    public function getBike(int $bikeId): Response
    {
        return $this->get("/bikes/{$bikeId}");
    }

    public function deleteBike(int $bikeId): Response
    {
        return $this->delete("/bikes/{$bikeId}");
    }

    public function createBikeWithImage(array $data, ?string $imagePath = null): Response
    {
        $request = Http::withHeaders($this->getHeaders())
            ->timeout(60)
            ->connectTimeout(60);

        if ($imagePath && file_exists($imagePath)) {
            $request = $request->attach('image', file_get_contents($imagePath), basename($imagePath));
        }

        return $this->handle($request->post($this->getFullUrl('/bikes/upload'), $data));
    }

    // ---------------------------------------------------------------
    // Bookings
    // ---------------------------------------------------------------

    public function quoteBooking(int $bikeId, array $data): Response
    {
        return $this->post("/bookings/quote/{$bikeId}", $data);
    }

    public function createBooking(int $bikeId, array $data): Response
    {
        return $this->post("/bookings/{$bikeId}", $data);
    }

    public function myBookings(): Response
    {
        return $this->get('/bookings/me');
    }

    public function ownerBookings(): Response
    {
        return $this->get('/bookings/owner');
    }

    public function approveBooking(int $bookingId): Response
    {
        return $this->post("/bookings/{$bookingId}/approve");
    }

    public function rejectBooking(int $bookingId): Response
    {
        return $this->post("/bookings/{$bookingId}/reject");
    }

    public function completeBooking(int $bookingId): Response
    {
        return $this->post("/bookings/{$bookingId}/complete");
    }

    public function extendBooking(int $bookingId, int $extraHours): Response
    {
        return $this->post("/bookings/{$bookingId}/extend", ['extra_hours' => $extraHours]);
    }

    // ---------------------------------------------------------------
    // Wallet
    // ---------------------------------------------------------------

    public function walletBalance(): Response
    {
        return $this->get('/wallet/balance');
    }

    public function walletTransactions(): Response
    {
        return $this->get('/wallet/transactions');
    }

    public function walletTopup(float $amount): Response
    {
        return $this->post('/wallet/topup', ['amount' => $amount]);
    }

    // ---------------------------------------------------------------
    // Users / Profile
    // ---------------------------------------------------------------

    public function updateProfile(array $data): Response
    {
        return $this->patch('/users/me', $data);
    }

    public function updatePassword(string $currentPassword, string $newPassword): Response
    {
        return $this->post('/users/me/password', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
        ]);
    }

    public function switchMode(): Response
    {
        return $this->post('/users/me/switch-mode');
    }

    // ---------------------------------------------------------------
    // Favorites
    // ---------------------------------------------------------------

    public function favorites(): Response
    {
        return $this->get('/favorites');
    }

    public function addFavorite(int $bikeId): Response
    {
        return $this->post("/favorites/{$bikeId}");
    }

    public function removeFavorite(int $bikeId): Response
    {
        return $this->delete("/favorites/{$bikeId}");
    }

    // ---------------------------------------------------------------
    // Offers
    // ---------------------------------------------------------------

    public function listOffers(): Response
    {
        return $this->get('/offers');
    }

    public function claimOfferByCode(string $code): Response
    {
        return $this->post('/offers/claim', ['code' => $code]);
    }

    // ---------------------------------------------------------------
    // Reviews
    // ---------------------------------------------------------------

    public function myReviews(): Response
    {
        return $this->get('/reviews/mine');
    }

    public function bikeReviews(int $bikeId): Response
    {
        return $this->get("/reviews/bike/{$bikeId}");
    }

    public function createReview(array $data): Response
    {
        return $this->post('/reviews', $data);
    }

    // ---------------------------------------------------------------
    // Agreements
    // ---------------------------------------------------------------

    public function generateAgreement(int $bookingId): Response
    {
        return $this->post("/agreements/{$bookingId}/generate");
    }

    public function downloadAgreement(int $bookingId): Response
    {
        return $this->get("/agreements/{$bookingId}/download");
    }

    public function agreementStatus(int $bookingId): Response
    {
        return $this->get("/agreements/{$bookingId}/status");
    }

    // ---------------------------------------------------------------
    // AI Features
    // ---------------------------------------------------------------

    public function aiChat(string $message): Response
    {
        return $this->post('/ai/chat', ['message' => $message]);
    }

    public function aiRecommendBikes(array $data): Response
    {
        return $this->post('/ai/recommend-bike', $data);
    }

    public function aiPredictPrice(array $data): Response
    {
        return $this->post('/ai/price-prediction', $data);
    }

    public function aiForecastDemand(array $data): Response
    {
        return $this->post('/ai/demand-forecast', $data);
    }

    public function aiDetectFraud(array $data): Response
    {
        return $this->post('/ai/fraud-detection', $data);
    }

    public function aiPredictMaintenance(int $bikeId): Response
    {
        return $this->post('/ai/maintenance-prediction', ['bike_id' => $bikeId]);
    }

    public function aiAnalyzeReview(string $review): Response
    {
        return $this->post('/ai/review-analysis', ['review' => $review]);
    }

    public function aiAnalyzeAgreement(string $agreement): Response
    {
        return $this->post('/ai/agreement-analysis', ['agreement' => $agreement]);
    }

    public function aiSemanticSearch(string $query, int $topK = 5): Response
    {
        return $this->post('/ai/semantic-search', ['query' => $query, 'top_k' => $topK]);
    }
}