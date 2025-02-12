<?php

namespace Tests\Unit;

use App\Services\BestSellersService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class  BestSellersServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_returns_data_when_api_call_is_successful()
    {
        $params = [
            'author' => 'Alban Afmeti',
            'title' => 'Test Book',
            'isbn' => ['1111111111111', '2222222222222'],
            'offset' => 0,
        ];

        $expectedResponse = [
            'results' => [
                [
                    'title' => 'Test Book',
                    'author' => 'Alban Afmeti',
                    'isbn' => '1111111111111,2222222222222',
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($expectedResponse, 200),
        ]);

        $service = new BestSellersService();
        $result = $service->getBestSellers($params);

        $this->assertEquals($expectedResponse, $result);

        $expectedQuery = [
            'author' => 'Alban Afmeti',
            'title' => 'Test Book',
            'isbn' => '1111111111111,2222222222222',
            'offset' => 0,
        ];

        $apiKey = config('services.nyt.api_key');
        if ($apiKey) {
            $expectedQuery['api-key'] = $apiKey;
        }

        Http::assertSent(function ($request) use ($expectedQuery) {
            $parsedUrl = parse_url($request->url());
            parse_str($parsedUrl['query'] ?? '', $query);

            foreach ($expectedQuery as $key => $value) {
                if (!isset($query[$key]) || $query[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }

    public function test_it_throws_an_exception_when_the_api_call_fails()
    {
        $params = [
            'author' => 'Alban Afmeti',
        ];

        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $service = new BestSellersService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API request failed with status 500');

        $service->getBestSellers($params);
    }

    public function test_it_uses_cache_to_store_the_response()
    {
        $params = [
            'title' => 'Cached Book',
        ];

        $expectedResponse = [
            'results' => [
                [
                    'title' => 'Cached Book',
                    'author' => 'Alban Afmeti',
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($expectedResponse, 200),
        ]);

        $service = new BestSellersService();

        $result1 = $service->getBestSellers($params);

        $result2 = $service->getBestSellers($params);

        $this->assertEquals($result1, $result2);

        Http::assertSentCount(1);
    }
}
