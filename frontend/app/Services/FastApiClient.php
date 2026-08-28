<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Client\PendingRequest;

class FastApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $url = config('services.fastapi.base_url') 
            ?: env('API_BASE_URL') 
            ?: env('VITE_API_BASE_URL') 
            ?: 'https://volvex-bike-rental.onrender.com/api/v1';

        if (empty($url)) {
            $url = 'https://volvex-bike-rental.onrender.com/api/v1';
        }

        $this->baseUrl = rtrim($url, '/');
    }

    protected function client(): PendingRequest
    {
        $targetUrl = !empty($this->baseUrl) 
            ? $this->baseUrl 
            : 'https://volvex-bike-rental.onrender.com/api/v1';

        $request = Http::baseUrl($targetUrl)->acceptJson();

        if ($token = Session::get('fastapi_token')) {
            $request = $request->withToken($token);
        }

        return $request;
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

    // Generic HTTP Verbs
    public function post(string $endpoint, array $data = [])
    {
        return $this->client()->post($endpoint, $data);
    }

    public function get(string $endpoint, array $query = [])
    {
        return $this->client()->get($endpoint, $query);
    }

    public function put(string $endpoint, array $data = [])
    {
        return $this->client()->put($endpoint, $data);
    }

    public function delete(string $endpoint, array $data = [])
    {
        return $this->client()->delete($endpoint, $data);
    }
}