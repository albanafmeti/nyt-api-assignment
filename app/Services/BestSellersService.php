<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BestSellersService
{
    protected string $baseUrl;
    protected mixed $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json';
        $this->apiKey  = config('services.nyt.api_key');
    }

    /**
     *
     * Get bestsellers from NYT API.
     * @param array $params
     * @return array
     */
    public function getBestSellers(array $params): array
    {
        $query = [];
        if (isset($params['author'])) {
            $query['author'] = $params['author'];
        }
        if (isset($params['title'])) {
            $query['title'] = $params['title'];
        }
        if (isset($params['isbn'])) {
            $query['isbn'] = implode(',', $params['isbn']);
        }
        if (isset($params['offset'])) {
            $query['offset'] = $params['offset'];
        }

        $query['api-key'] = $this->apiKey;

        $cacheKey = 'best_sellers:' . md5(json_encode($query));

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($query) {
            $response = Http::get($this->baseUrl, $query);

            if ($response->failed()) {
                throw new \Exception('API request failed with status ' . $response->status());
            }

            return $response->json();
        });
    }
}
