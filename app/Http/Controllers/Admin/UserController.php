<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Http\Requests\Admin\SearchUsersRequest;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * List all users with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $users = $this->userRepository->getPaginated($perPage);

        return response()->json([
            'error' => false,
            'message' => 'Users retrieved successfully',
            'users' => UserResource::collection($users),
        ]);
    }

    /**
     * Search users based on criteria.
     */
    public function search(SearchUsersRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $users = $this->userRepository->search($validated['term']);

        return response()->json([
            'error' => false,
            'message' => 'Users retrieved successfully',
            'users' => UserResource::collection($users),
        ]);
    }

    /**
     * Show a specific user with their details.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'error' => false,
            'message' => 'User retrieved successfully',
            'user' => new UserResource($user),
        ]);
    }
}
