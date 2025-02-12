<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BestSellersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_returns_successful_response_with_valid_parameters()
    {
        $fakeResponse = [
            'num_results' => 1,
            'results' => [
                [
                    'title' => 'Sample Book Title',
                    'author' => 'Alban Afmeti',
                    'isbn13' => '1234567890123',
                ],
            ],
        ];

        Http::fake([
            'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json*' => Http::response($fakeResponse, 200),
        ]);

        $params = [
            'author' => 'Alban Afmeti',
            'isbn' => ['1234567890123', '9876543210987'],
            'title' => 'Sample Book Title',
            'offset' => 0,
        ];

        $response = $this->json('GET', '/api/v1/best-sellers', $params);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => $fakeResponse
        ]);
    }

    public function test_it_validates_input_parameters()
    {
        $params = [
            'offset' => 'not-an-integer',
        ];

        $response = $this->json('GET', '/api/v1/best-sellers', $params);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offset']);
    }

    public function test_it_handles_nyt_api_failure()
    {
        Http::fake([
            'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json*' => Http::response(null, 500),
        ]);

        $params = [
            'author' => 'Alban Afmeti',
        ];

        $response = $this->json('GET', '/api/v1/best-sellers', $params);

        $response->assertStatus(500)
            ->assertJsonStructure(['success', 'message'])
            ->assertJson([
                'success' => false,
                'message' => 'Failed to fetch data from NYT API.',
            ]);
    }

    public function test_it_caches_the_results()
    {
        $fakeResponse = [
            'num_results' => 1,
            'results' => [
                [
                    'title' => 'Sample Book Title',
                    'author' => 'Alban Afmeti',
                    'isbn13' => '1234567890123',
                ],
            ],
        ];

        Http::fake([
            'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json*' => Http::response($fakeResponse, 200),
        ]);

        $params = [
            'title' => 'Sample Book Title',
        ];

        // First call stores the result in the cache.
        $this->json('GET', '/api/v1/best-sellers', $params)->assertStatus(200);

        // Change the fake to a different response – the cached response should still be returned.
        Http::fake([
            'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json*' => Http::response(['results' => [], 'num_results' => 1], 200),
        ]);

        $this->json('GET', '/api/v1/best-sellers', $params)
            ->assertJson([
                'success' => true,
                'data' => $fakeResponse
            ]);
    }
}
