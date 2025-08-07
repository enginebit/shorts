<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateProjectRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProjectsController extends Controller
{
    /**
     * Display a listing of workspaces for the authenticated user.
     *
     * GET /api/projects
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's workspaces with role information
        $projects = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with([
            'users' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->select(['user_id', 'project_id', 'role']);
            },
            'domains' => function ($query) {
                $query->select(['id', 'project_id', 'slug', 'primary', 'verified']);
            },
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform the response to match dub-main format
        $transformedProjects = $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'logo' => $project->logo,
                'usage' => $project->usage,
                'usageLimit' => $project->usage_limit,
                'plan' => $project->plan,
                'stripeId' => $project->stripe_id,
                'billingCycleStart' => $project->billing_cycle_start,
                'createdAt' => $project->created_at,
                'updatedAt' => $project->updated_at,
                'role' => $project->users->first()?->role ?? 'member',
                'domains' => $project->domains,
            ];
        });

        return response()->json($transformedProjects);
    }

    /**
     * Store a newly created workspace.
     *
     * POST /api/projects
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Create project
            $project = Project::create([
                'id' => 'proj_'.Str::random(24),
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'logo' => $validated['logo'] ?? null,
                'plan' => 'free',
                'usage' => 0,
                'usage_limit' => 1000, // Default free plan limit
                'links_limit' => 25,
                'domains_limit' => 3,
                'tags_limit' => 5,
                'users_limit' => 1,
                'invite_code' => Str::random(24),
            ]);

            // Add user as owner
            $project->users()->attach($user->id, [
                'role' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set as default workspace if user doesn't have one
            if (! $user->default_workspace) {
                $user->update(['default_workspace' => $project->id]);
            }

            DB::commit();

            // Load relationships for response
            $project->load([
                'users' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'domains',
            ]);

            return response()->json([
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'logo' => $project->logo,
                'usage' => $project->usage,
                'usageLimit' => $project->usage_limit,
                'plan' => $project->plan,
                'createdAt' => $project->created_at,
                'updatedAt' => $project->updated_at,
                'role' => 'owner',
                'domains' => $project->domains,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create workspace',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified workspace.
     *
     * GET /api/projects/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find project with user access verification
        $project = Project::where('id', $id)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'users' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'domains',
                'links' => function ($query) {
                    $query->limit(10)->orderBy('created_at', 'desc');
                },
            ])
            ->first();

        if (! $project) {
            return response()->json([
                'error' => 'Workspace not found',
                'message' => 'Workspace not found or access denied',
            ], 404);
        }

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'logo' => $project->logo,
            'usage' => $project->usage,
            'usageLimit' => $project->usage_limit,
            'plan' => $project->plan,
            'stripeId' => $project->stripe_id,
            'billingCycleStart' => $project->billing_cycle_start,
            'createdAt' => $project->created_at,
            'updatedAt' => $project->updated_at,
            'role' => $project->users->first()?->role ?? 'member',
            'domains' => $project->domains,
            'recentLinks' => $project->links,
        ]);
    }

    /**
     * Update the specified workspace.
     *
     * PUT /api/projects/{id}
     */
    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Find project with owner access verification
        $project = Project::where('id', $id)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('role', 'owner');
            })
            ->first();

        if (! $project) {
            return response()->json([
                'error' => 'Workspace not found',
                'message' => 'Workspace not found or insufficient permissions',
            ], 404);
        }

        try {
            $project->update($validated);

            return response()->json([
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'logo' => $project->logo,
                'usage' => $project->usage,
                'usageLimit' => $project->usage_limit,
                'plan' => $project->plan,
                'updatedAt' => $project->updated_at,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update workspace',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified workspace.
     *
     * DELETE /api/projects/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find project with owner access verification
        $project = Project::where('id', $id)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('role', 'owner');
            })
            ->first();

        if (! $project) {
            return response()->json([
                'error' => 'Workspace not found',
                'message' => 'Workspace not found or insufficient permissions',
            ], 404);
        }

        // Check if workspace has links
        $linkCount = $project->links()->count();
        if ($linkCount > 0) {
            return response()->json([
                'error' => 'Workspace has links',
                'message' => "Cannot delete workspace with {$linkCount} existing links",
            ], 409);
        }

        try {
            // If this was the user's default workspace, clear it
            if ($user->default_workspace === $project->id) {
                $user->update(['default_workspace' => null]);
            }

            $project->delete();

            return response()->json([
                'message' => 'Workspace deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete workspace',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
