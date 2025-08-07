<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateDomainRequest;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DomainsController extends Controller
{
    /**
     * Display a listing of domains for the authenticated user's workspace.
     *
     * GET /api/domains
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

        // Build query with filters
        $query = Domain::where('project_id', $workspaceId);

        // Apply search filter
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('slug', 'like', "%{$search}%");
        }

        // Apply archived filter
        if ($request->has('archived')) {
            $query->where('archived', $request->boolean('archived'));
        }

        // Pagination
        $page = (int) $request->query('page', 1);
        $pageSize = min((int) $request->query('pageSize', 50), 100);

        $domains = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'domains' => $domains->items(),
            'pagination' => [
                'page' => $domains->currentPage(),
                'pageSize' => $domains->perPage(),
                'total' => $domains->total(),
                'totalPages' => $domains->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created domain.
     *
     * POST /api/domains
     */
    public function store(CreateDomainRequest $request): JsonResponse
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

        // Check if domain already exists
        $existingDomain = Domain::where('slug', $validated['slug'])->first();
        if ($existingDomain) {
            return response()->json([
                'error' => 'Domain already exists',
                'message' => "Domain '{$validated['slug']}' is already registered",
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Create domain
            $domain = Domain::create([
                'id' => 'domain_'.Str::random(24),
                'slug' => $validated['slug'],
                'verified' => false,
                'primary' => $validated['primary'] ?? false,
                'archived' => false,
                'project_id' => $workspaceId,
                'placeholder' => $validated['placeholder'] ?? 'Enter your link here...',
                'expiredUrl' => $validated['expired_url'] ?? null,
                'notFoundUrl' => $validated['not_found_url'] ?? null,
            ]);

            DB::commit();

            return response()->json($domain, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create domain',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified domain.
     *
     * GET /api/domains/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find domain with workspace access verification
        $domain = Domain::where('id', $id)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['project'])
            ->first();

        if (! $domain) {
            return response()->json([
                'error' => 'Domain not found',
                'message' => 'Domain not found or access denied',
            ], 404);
        }

        return response()->json($domain);
    }

    /**
     * Verify domain ownership.
     *
     * PUT /api/domains/{id}/verify
     */
    public function verify(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find domain with workspace access verification
        $domain = Domain::where('id', $id)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $domain) {
            return response()->json([
                'error' => 'Domain not found',
                'message' => 'Domain not found or access denied',
            ], 404);
        }

        try {
            // Placeholder for domain verification logic
            // In production, this would check DNS records, SSL certificates, etc.
            $verified = $this->performDomainVerification($domain->slug);

            $domain->update([
                'verified' => $verified,
                'verified_at' => $verified ? now() : null,
            ]);

            return response()->json([
                'domain' => $domain->fresh(),
                'verified' => $verified,
                'message' => $verified ? 'Domain verified successfully' : 'Domain verification failed',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Verification failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified domain.
     *
     * DELETE /api/domains/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Find domain with workspace access verification
        $domain = Domain::where('id', $id)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $domain) {
            return response()->json([
                'error' => 'Domain not found',
                'message' => 'Domain not found or access denied',
            ], 404);
        }

        // Check if domain has links
        $linkCount = $domain->links()->count();
        if ($linkCount > 0) {
            return response()->json([
                'error' => 'Domain has links',
                'message' => "Cannot delete domain with {$linkCount} existing links",
            ], 409);
        }

        try {
            $domain->delete();

            return response()->json([
                'message' => 'Domain deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete domain',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform domain verification.
     * This is a placeholder implementation.
     */
    private function performDomainVerification(string $domain): bool
    {
        // Placeholder implementation
        // In production, this would:
        // 1. Check DNS records
        // 2. Verify SSL certificate
        // 3. Test HTTP/HTTPS connectivity
        // 4. Validate domain ownership via TXT records

        return true; // Always return true for demo purposes
    }
}
