<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ProjectRepository;
use App\Http\Requests\User\SearchProjectsRequest;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {}

    public function index(SearchProjectsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 10;
        $projects = $this->projectRepository->getFilteredProjects($user, $validated);

        return response()->json([
            'error' => false,
            'message' => 'Projects retrieved successfully',
            'data' => $projects,
        ]);
    }

    /**
     * Show a specific project with its details.
     */
    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'error' => false,
            'message' => 'Project retrieved successfully',
            'data' => $project,
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->projectRepository->delete($project);

        return response()->json([
            'error' => false,
            'message' => 'Project deleted successfully',
        ]);
    }
}
