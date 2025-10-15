<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardRepository $dashboardRepository
    ) {}

    /**
     * Get dashboard statistics for the authenticated user.
     */
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $stats = $this->dashboardRepository->getDashboardStats($user);

        return response()->json([
            'error' => false,
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => $stats,
        ]);
    }
}
