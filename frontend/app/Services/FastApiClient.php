<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class FastApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.fastapi.base_url'), '/');
    }

    protected function client(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)->acceptJson();

        if ($token = Session::get('fastapi_token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    protected function handle(Response $response): Response
    {
        if ($response->failed()) {
            $detail = $response->json('detail') ?? 'The backend returned an unexpected error.';
            throw new FastApiException($detail, $response->status(), $response);
        }

        return $response;
    }

    public function register(array $payload): Response
    {
        return $this->handle($this->client()->post('/auth/register', $payload));
    }

    public function login(string $email, string $password): Response
    {
        return $this->handle($this->client()->post('/auth/login', [
            'email' => $email,
            'password' => $password,
        ]));
    }

    public function me(): Response
    {
        return $this->handle($this->client()->get('/auth/me'));
    }

    public function listBikes(array $filters = []): Response
    {
        return $this->handle($this->client()->get('/bikes', $filters));
    }

    public function recommendBikes(string $location): Response
    {
        return $this->handle($this->client()->get('/bikes/recommendations', [
            'location' => $location,
        ]));
    }

    public function myBikes(): Response
    {
        return $this->handle($this->client()->get('/bikes/mine'));
    }

    public function getBike(int $bikeId): Response
    {
        return $this->handle($this->client()->get("/bikes/{$bikeId}"));
    }

    public function createBike(array $payload): Response
    {
        return $this->handle($this->client()->post('/bikes', $payload));
    }

    public function createBikeWithImage(array $formData, ?string $imagePath = null): Response
    {
        // Build multipart payload manually
        $multipart = [];

        // Add regular form fields
        foreach ($formData as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => (string) $value,
            ];
        }

        // Add image file if present
        if ($imagePath && file_exists($imagePath)) {
            $multipart[] = [
                'name' => 'image',
                'contents' => fopen($imagePath, 'r'),
                'filename' => basename($imagePath),
            ];
        }

        $request = $this->client()->asMultipart();

        return $this->handle($request->post('/bikes/upload', $multipart));
    }

    public function deleteBike(int $bikeId): Response
    {
        return $this->handle($this->client()->delete("/bikes/{$bikeId}"));
    }

    public function createBooking(int $bikeId, array $payload): Response
    {
        return $this->handle($this->client()->post("/bookings/{$bikeId}", $payload));
    }

    public function quoteBooking(int $bikeId, array $payload): Response
    {
        return $this->handle($this->client()->post("/bookings/quote/{$bikeId}", $payload));
    }

    public function myBookings(): Response
    {
        return $this->handle($this->client()->get('/bookings/me'));
    }

    public function ownerBookings(): Response
    {
        return $this->handle($this->client()->get('/bookings/owner'));
    }

    public function approveBooking(int $bookingId): Response
    {
        return $this->handle($this->client()->post("/bookings/{$bookingId}/approve"));
    }

    public function rejectBooking(int $bookingId): Response
    {
        return $this->handle($this->client()->post("/bookings/{$bookingId}/reject"));
    }

    public function completeBooking(int $bookingId): Response
    {
        return $this->handle($this->client()->post("/bookings/{$bookingId}/complete"));
    }

    public function extendBooking(int $bookingId, int $extraHours = 1): Response
    {
        return $this->handle($this->client()->post("/bookings/{$bookingId}/extend", [
            'extra_hours' => $extraHours,
        ]));
    }

    public function walletBalance(): Response
    {
        return $this->handle($this->client()->get('/wallet/balance'));
    }

    public function walletTransactions(): Response
    {
        return $this->handle($this->client()->get('/wallet/transactions'));
    }

    public function walletTopup(float $amount): Response
    {
        return $this->handle($this->client()->post('/wallet/topup', ['amount' => $amount]));
    }

    public function updateProfile(array $payload): Response
    {
        return $this->handle($this->client()->patch('/users/me', $payload));
    }

    public function updatePassword(string $currentPassword, string $newPassword): Response
    {
        return $this->handle($this->client()->post('/users/me/password', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
        ]));
    }

    public function switchMode(): Response
    {
        return $this->handle($this->client()->post('/users/me/switch-mode'));
    }

    // --- Favorites ---

    public function favorites(): Response
    {
        return $this->handle($this->client()->get('/favorites'));
    }

    public function addFavorite(int $bikeId): Response
    {
        return $this->handle($this->client()->post("/favorites/{$bikeId}"));
    }

    public function removeFavorite(int $bikeId): Response
    {
        return $this->handle($this->client()->delete("/favorites/{$bikeId}"));
    }

    // --- Offers ---

    public function listOffers(): Response
    {
        return $this->handle($this->client()->get('/offers'));
    }

    public function claimOfferByCode(string $code): Response
    {
        return $this->handle($this->client()->post('/offers/claim', ['code' => $code]));
    }

    // --- Reviews ---

    public function createReview(array $payload): Response
    {
        return $this->handle($this->client()->post('/reviews', $payload));
    }

    public function bikeReviews(int $bikeId): Response
    {
        return $this->handle($this->client()->get("/reviews/bike/{$bikeId}"));
    }

    public function myReviews(): Response
    {
        return $this->handle($this->client()->get('/reviews/mine'));
    }

    // --- Agreements ---

    public function generateAgreement(int $bookingId): Response
    {
        return $this->handle($this->client()->post("/agreements/{$bookingId}/generate"));
    }

    public function agreementStatus(int $bookingId): Response
    {
        return $this->handle($this->client()->get("/agreements/{$bookingId}/status"));
    }

    public function downloadAgreement(int $bookingId): Response
    {
        return $this->handle($this->client()->get("/agreements/{$bookingId}/download"));
    }

    // --- AI Features ---

    public function aiChat(string $message): Response
    {
        return $this->handle($this->client()->post('/ai/chat', ['message' => $message]));
    }

    public function aiRecommendBikes(array $payload): Response
    {
        return $this->handle($this->client()->post('/ai/recommend-bike', $payload));
    }

    public function aiPredictPrice(array $payload): Response
    {
        return $this->handle($this->client()->post('/ai/price-prediction', $payload));
    }

    public function aiPredictPriceByBike(int $bikeId): Response
    {
        return $this->handle($this->client()->get("/ai/price-prediction/{$bikeId}"));
    }

    public function aiForecastDemand(array $payload): Response
    {
        return $this->handle($this->client()->post('/ai/demand-forecast', $payload));
    }

    public function aiDetectFraud(array $payload): Response
    {
        return $this->handle($this->client()->post('/ai/fraud-detection', $payload));
    }

    public function aiPredictMaintenance(int $bikeId): Response
    {
        return $this->handle($this->client()->post('/ai/maintenance-prediction', ['bike_id' => $bikeId]));
    }

    public function aiAnalyzeReview(string $review): Response
    {
        return $this->handle($this->client()->post('/ai/review-analysis', ['review' => $review]));
    }

    public function aiAnalyzeAgreement(string $agreement): Response
    {
        return $this->handle($this->client()->post('/ai/agreement-analysis', ['agreement' => $agreement]));
    }

    public function aiSemanticSearch(string $query, int $topK = 5): Response
    {
        return $this->handle($this->client()->post('/ai/semantic-search', [
            'query' => $query,
            'top_k' => $topK,
        ]));
    }
}
