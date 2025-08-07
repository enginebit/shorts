<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateLinkRequest;
use App\Http\Requests\Api\UpdateLinkRequest;
use App\Models\Link;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LinksController extends Controller
{
    /**
     * Display a listing of links for the authenticated user's workspace.
     *
     * GET /api/links
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's default workspace or specified workspace
        $workspaceId = $request->query('workspace_id', $user->default_workspace);

        if (! $workspaceId) {
            return response()->json([
                'error' => 'No workspace specified',
                'message' => 'Please specify a workspace_id or set a default workspace',
            ], 400);
        }

        // Verify user has access to workspace
        $workspace = Project::where('id', $workspaceId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $workspace) {
            return response()->json([
                'error' => 'Workspace not found',
                'message' => 'Workspace not found or access denied',
            ], 404);
        }

        // Build query with filters following dub-main patterns
        $query = Link::where('project_id', $workspaceId);

        // Apply filters
        if ($request->has('domain')) {
            $query->where('domain', $request->query('domain'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('tag_id')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tag_id', $request->query('tag_id'));
            });
        }

        // Pagination following dub-main patterns
        $page = (int) $request->query('page', 1);
        $pageSize = min((int) $request->query('pageSize', 100), 100);

        $links = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'links' => $links->items(),
            'pagination' => [
                'page' => $links->currentPage(),
                'pageSize' => $links->perPage(),
                'total' => $links->total(),
                'totalPages' => $links->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created link.
     *
     * POST /api/links
     */
    public function store(CreateLinkRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Get workspace
        $workspaceId = $validated['project_id'] ?? $user->default_workspace;

        if (! $workspaceId) {
            return response()->json([
                'error' => 'No workspace specified',
                'message' => 'Please specify a project_id or set a default workspace',
            ], 400);
        }

        // Verify workspace access
        $workspace = Project::where('id', $workspaceId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $workspace) {
            return response()->json([
                'error' => 'Workspace not found',
                'message' => 'Workspace not found or access denied',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Generate short key if not provided
            $key = $validated['key'] ?? $this->generateShortKey();

            // Check if key already exists for this domain
            $domain = $validated['domain'] ?? 'dub.sh';
            $existingLink = Link::where('domain', $domain)
                ->where('key', $key)
                ->first();

            if ($existingLink) {
                return response()->json([
                    'error' => 'Key already exists',
                    'message' => "Link with key '{$key}' already exists for domain '{$domain}'",
                ], 409);
            }

            // Create link following dub-main patterns
            $link = Link::create([
                'id' => 'link_'.Str::random(24),
                'domain' => $domain,
                'key' => $key,
                'url' => $validated['url'],
                'short_link' => "https://{$domain}/{$key}",
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'image' => $validated['image'] ?? null,
                'project_id' => $workspaceId,
                'user_id' => $user->id,
                'public_stats' => $validated['public_stats'] ?? false,
                'password' => $validated['password'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'ios' => $validated['ios'] ?? null,
                'android' => $validated['android'] ?? null,
                'geo' => $validated['geo'] ?? null,
                'utm_source' => $validated['utm_source'] ?? null,
                'utm_medium' => $validated['utm_medium'] ?? null,
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'utm_term' => $validated['utm_term'] ?? null,
                'utm_content' => $validated['utm_content'] ?? null,
            ]);

            DB::commit();

            return response()->json($link, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified link.
     *
     * GET /api/links/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find link with workspace access verification
        $link = Link::where('id', $id)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['project'])
            ->first();

        if (! $link) {
            return response()->json([
                'error' => 'Link not found',
                'message' => 'Link not found or access denied',
            ], 404);
        }

        return response()->json($link);
    }

    /**
     * Update the specified link.
     *
     * PUT/PATCH /api/links/{id}
     */
    public function update(UpdateLinkRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Find link with workspace access verification
        $link = Link::where('id', $id)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $link) {
            return response()->json([
                'error' => 'Link not found',
                'message' => 'Link not found or access denied',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Check if key is being changed and if it conflicts
            if (isset($validated['key']) && $validated['key'] !== $link->key) {
                $existingLink = Link::where('domain', $link->domain)
                    ->where('key', $validated['key'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingLink) {
                    return response()->json([
                        'error' => 'Key already exists',
                        'message' => "Link with key '{$validated['key']}' already exists for domain '{$link->domain}'",
                    ], 409);
                }
            }

            // Update link
            $link->update($validated);

            DB::commit();

            // Load relationships for response
            $link->load(['project']);

            return response()->json($link);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified link.
     *
     * DELETE /api/links/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find link with workspace access verification
        $link = Link::where('id', $id)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $link) {
            return response()->json([
                'error' => 'Link not found',
                'message' => 'Link not found or access denied',
            ], 404);
        }

        try {
            $link->delete();

            return response()->json([
                'message' => 'Link deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a random short key for links.
     */
    private function generateShortKey(): string
    {
        do {
            $key = Str::random(7);
            $exists = Link::where('key', $key)->exists();
        } while ($exists);

        return $key;
    }
}
