<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

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

        Log::info('FastApiClient initialized with base_url: ' . $this->baseUrl);
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

    // Auth Helper Methods
    public function register(array $data)
    {
        return $this->post('/auth/register', $data);
    }

    public function login(array $credentials)
    {
        return $this->post('/auth/login', $credentials);
    }

    // Direct HTTP Request Methods with Full URLs
    public function post(string $endpoint, array $data = [])
    {
        return Http::withHeaders($this->getHeaders())->post($this->getFullUrl($endpoint), $data);
    }

    public function get(string $endpoint, array $query = [])
    {
        return Http::withHeaders($this->getHeaders())->get($this->getFullUrl($endpoint), $query);
    }

    public function put(string $endpoint, array $data = [])
    {
        return Http::withHeaders($this->getHeaders())->put($this->getFullUrl($endpoint), $data);
    }

    public function delete(string $endpoint, array $data = [])
    {
        return Http::withHeaders($this->getHeaders())->delete($this->getFullUrl($endpoint), $data);
    }
}