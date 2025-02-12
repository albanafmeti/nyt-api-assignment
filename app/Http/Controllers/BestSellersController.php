<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetBestSellersRequest;
use App\Services\BestSellersService;

class BestSellersController extends Controller
{
    protected BestSellersService $bestSellersService;

    public function __construct(BestSellersService $bestSellersService)
    {
        $this->bestSellersService = $bestSellersService;
    }

    public function getList(GetBestSellersRequest $request): \Illuminate\Http\JsonResponse
    {
        $queryParams = $request->validated();

        try {
            $data = $this->bestSellersService->getBestSellers($queryParams);
            return response()->json([
                'success' => true,
                'data' => [
                    'results' => $data['results'],
                    'num_results' => $data['num_results'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data from NYT API.',
            ], 500);
        }
    }
}
