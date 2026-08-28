public function __construct()
    {
        $url = config('services.fastapi.base_url') 
            ?: env('API_BASE_URL') 
            ?: env('VITE_API_BASE_URL') 
            ?: 'https://volvex-bike-rental.onrender.com/api/v1';

        // Fallback if URL still resolves empty
        if (empty($url)) {
            $url = 'https://volvex-bike-rental.onrender.com/api/v1';
        }

        $this->baseUrl = rtrim($url, '/');
    }

    protected function client(): PendingRequest
    {
        // Absolute fallback check directly before sending HTTP request
        $targetUrl = !empty($this->baseUrl) 
            ? $this->baseUrl 
            : 'https://volvex-bike-rental.onrender.com/api/v1';

        $request = Http::baseUrl($targetUrl)->acceptJson();

        if ($token = Session::get('fastapi_token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }