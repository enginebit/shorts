<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Workspace\CreateWorkspaceRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WorkspaceController
 *
 * Based on dub-main API routes: /api/workspaces
 *
 * Handles workspace management including creation, updating, deletion,
 * and user access control following dub-main patterns.
 */
final class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService
    ) {}

    /**
     * Display a listing of workspaces for the authenticated user
     *
     * Based on: GET /api/workspaces
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $workspaces = $user->workspaces()
            ->with(['domains' => function ($query) {
                $query->select('id', 'slug', 'primary', 'verified', 'workspace_id')
                    ->take(100);
            }])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($workspace) use ($user) {
                return [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug,
                    'logo' => $workspace->logo,
                    'plan' => $workspace->plan,
                    'usage' => $workspace->usage,
                    'usageLimit' => $workspace->usage_limit,
                    'linksUsage' => $workspace->links_usage,
                    'linksLimit' => $workspace->links_limit,
                    'domainsLimit' => $workspace->domains_limit,
                    'usersLimit' => $workspace->users_limit,
                    'createdAt' => $workspace->created_at,
                    'users' => [
                        [
                            'role' => $workspace->getUserRole($user),
                            'defaultFolderId' => $workspace->pivot->default_folder_id,
                        ],
                    ],
                    'domains' => $workspace->domains->map(function ($domain) {
                        return [
                            'slug' => $domain->slug,
                            'primary' => $domain->primary,
                            'verified' => $domain->verified,
                        ];
                    }),
                ];
            });

        return response()->json($workspaces);
    }

    /**
     * Show the form for creating a new workspace
     */
    public function create(): Response
    {
        return Inertia::render('Workspaces/Create');
    }

    /**
     * Store a newly created workspace
     *
     * Based on: POST /api/workspaces
     */
    public function store(CreateWorkspaceRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check if user can create more free workspaces
        if (! $user->canCreateFreeWorkspace()) {
            return response()->json([
                'message' => 'You can only create up to 2 free workspaces. Additional workspaces require a paid plan.',
            ], 403);
        }

        $workspace = $this->workspaceService->createWorkspace(
            $user,
            $request->validated()
        );

        return response()->json([
            'id' => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'logo' => $workspace->logo,
            'plan' => $workspace->plan,
            'createdAt' => $workspace->created_at,
            'users' => [
                [
                    'role' => 'owner',
                    'defaultFolderId' => null,
                ],
            ],
            'domains' => [],
        ], 201);
    }

    /**
     * Display the specified workspace
     *
     * Based on: GET /api/workspaces/[idOrSlug]
     */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $workspace = $this->workspaceService->findWorkspaceByIdOrSlug($idOrSlug);

        if (! $workspace || ! $workspace->isMember($request->user())) {
            return response()->json(['message' => 'Workspace not found'], 404);
        }

        $domains = $workspace->domains()
            ->select('id', 'slug', 'primary', 'verified', 'link_retention_days')
            ->take(100)
            ->get();

        return response()->json([
            'id' => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'logo' => $workspace->logo,
            'plan' => $workspace->plan,
            'usage' => $workspace->usage,
            'usageLimit' => $workspace->usage_limit,
            'linksUsage' => $workspace->links_usage,
            'linksLimit' => $workspace->links_limit,
            'domainsLimit' => $workspace->domains_limit,
            'usersLimit' => $workspace->users_limit,
            'conversionEnabled' => $workspace->conversion_enabled,
            'partnersEnabled' => $workspace->partners_enabled,
            'createdAt' => $workspace->created_at,
            'domains' => $domains,
            'users' => [
                [
                    'role' => $workspace->getUserRole($request->user()),
                    'defaultFolderId' => $workspace->pivot->default_folder_id ?? null,
                ],
            ],
        ]);
    }

    /**
     * Update the specified workspace
     *
     * Based on: PATCH /api/workspaces/[idOrSlug]
     */
    public function update(UpdateWorkspaceRequest $request, string $idOrSlug): JsonResponse
    {
        $workspace = $this->workspaceService->findWorkspaceByIdOrSlug($idOrSlug);

        if (! $workspace || ! $workspace->isOwner($request->user())) {
            return response()->json(['message' => 'Workspace not found or insufficient permissions'], 403);
        }

        $updatedWorkspace = $this->workspaceService->updateWorkspace(
            $workspace,
            $request->validated()
        );

        return response()->json([
            'id' => $updatedWorkspace->id,
            'name' => $updatedWorkspace->name,
            'slug' => $updatedWorkspace->slug,
            'logo' => $updatedWorkspace->logo,
            'plan' => $updatedWorkspace->plan,
            'conversionEnabled' => $updatedWorkspace->conversion_enabled,
            'updatedAt' => $updatedWorkspace->updated_at,
        ]);
    }

    /**
     * Remove the specified workspace
     *
     * Based on: DELETE /api/workspaces/[idOrSlug]
     */
    public function destroy(Request $request, string $idOrSlug): JsonResponse
    {
        $workspace = $this->workspaceService->findWorkspaceByIdOrSlug($idOrSlug);

        if (! $workspace || ! $workspace->isOwner($request->user())) {
            return response()->json(['message' => 'Workspace not found or insufficient permissions'], 403);
        }

        $this->workspaceService->deleteWorkspace($workspace);

        return response()->json(['message' => 'Workspace deleted successfully']);
    }
}
