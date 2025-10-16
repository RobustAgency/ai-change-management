<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\Admin\DashboardRepository;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardRepository $dashboardRepository
    ) {}

    /**
     * Get admin dashboard statistics.
     */
    public function __invoke(): JsonResponse
    {
        $stats = $this->dashboardRepository->getDashboardStats();

        return response()->json([
            'error' => false,
            'message' => 'Dashboard stats retrieved successfully',
            'data' => $stats,
        ]);
    }
}
