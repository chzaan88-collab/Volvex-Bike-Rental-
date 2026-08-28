<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class FastApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $url = config('services.fastapi.base_url') 
            ?: env('API_BASE_URL') 
            ?: env('VITE_API_BASE_URL') 
            ?: 'https://volvex-bike-rental.onrender.com/api/v1';

        $this->baseUrl = !empty($url) ? rtrim($url, '/') : 'https://volvex-bike-rental.onrender.com/api/v1';
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