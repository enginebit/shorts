# Workspace System Analysis & Migration Plan

## Executive Summary

This document provides a comprehensive analysis of the workspace system in the dub-main reference repository and presents a complete migration plan for implementing the workspace architecture in our Laravel + React + Inertia.js application.

**Key Findings:**
- Workspace system is the core architectural component of dub.co
- Complex multi-tenant architecture with user roles and permissions
- Sophisticated billing and usage tracking system
- Rich feature flag and limit management system
- Critical dependency for all other application features

## 1. Database Schema Analysis

### **Source Analysis: `/packages/prisma/schema/workspace.prisma`**

#### **Core Workspace Model (Project in dub-main)**
```prisma
model Project {
  id                    String   @id @default(cuid())
  name                  String
  slug                  String   @unique
  logo                  String?
  plan                  String   @default("free")
  stripeId              String?  @unique @map("stripe_id")
  billingCycleStart     Int      @map("billing_cycle_start")
  // ... 50+ additional fields
}
```

#### **Key Relationships Identified:**
- **ProjectUsers**: Many-to-many relationship with User model
- **ProjectInvite**: Pending workspace invitations
- **NotificationPreference**: User notification settings per workspace
- **Links**: All links belong to a workspace
- **Domains**: Custom domains per workspace
- **Tags**: Link organization within workspaces

#### **Critical Fields Analysis:**
- **Usage Tracking**: `usage`, `usageLimit`, `linksUsage`, `linksLimit`
- **Billing**: `plan`, `stripeId`, `billingCycleStart`, `paymentFailedAt`
- **Feature Limits**: `domainsLimit`, `tagsLimit`, `usersLimit`, `aiLimit`
- **Feature Flags**: `conversionEnabled`, `webhookEnabled`, `partnersEnabled`
- **JSON Storage**: `store` (key-value), `allowedHostnames` (array)

### **Laravel Migration Implementation**

Created comprehensive migration system:
- ✅ **workspaces** table with all dub-main fields
- ✅ **workspace_users** pivot table with roles
- ✅ **workspace_invites** for pending invitations
- ✅ **notification_preferences** for user settings
- ✅ **users** table extensions for workspace fields

## 2. Backend API Analysis

### **Source Analysis: `/apps/web/app/api/workspaces/`**

#### **Core API Endpoints Identified:**
- `GET /api/workspaces` - List user's workspaces
- `POST /api/workspaces` - Create new workspace
- `GET /api/workspaces/[idOrSlug]` - Get workspace details
- `PATCH /api/workspaces/[idOrSlug]` - Update workspace
- `DELETE /api/workspaces/[idOrSlug]` - Delete workspace
- `GET /api/workspaces/[idOrSlug]/users` - Workspace members
- `POST /api/workspaces/[idOrSlug]/invites` - Invite users

#### **Authentication & Authorization Patterns:**
- **Session-based authentication** with workspace context
- **Role-based permissions** (owner vs member)
- **Usage limit enforcement** before actions
- **Workspace membership validation** on all endpoints

#### **Business Logic Patterns:**
- **Free workspace limits** (max 2 per user)
- **Slug uniqueness** with reserved word checking
- **Automatic default workspace** assignment
- **Cascade deletion** with cleanup

### **Laravel Implementation**

Created complete backend system:
- ✅ **WorkspaceController** with all CRUD operations
- ✅ **WorkspaceService** for business logic
- ✅ **Form Requests** with dub-main validation rules
- ✅ **Eloquent Models** with proper relationships
- ✅ **Permission checking** and role management

## 3. Frontend Integration Analysis

### **Source Analysis: `/apps/web/ui/layout/sidebar/workspace-dropdown.tsx`**

#### **Key UI Patterns Identified:**
- **Workspace switching** via dropdown with visual feedback
- **Current workspace detection** from URL parameters
- **Workspace avatars** with fallback to generated images
- **Plan display** with color coding
- **Quick actions** (settings, invite members, create workspace)

#### **State Management Patterns:**
- **useWorkspaces hook** for data fetching with SWR
- **URL-based workspace context** (slug in path)
- **Optimistic updates** for workspace switching
- **Error handling** with fallback states

#### **Data Flow Architecture:**
```typescript
URL (/workspace-slug/...) → 
useWorkspaces() → 
WorkspaceContext → 
Components
```

### **React + Inertia.js Implementation**

Created comprehensive frontend system:
- ✅ **WorkspaceContext** with React Context API
- ✅ **useWorkspace hooks** for component integration
- ✅ **WorkspaceDropdown** with dub-main visual consistency
- ✅ **URL-based workspace detection** via Inertia.js
- ✅ **Permission-based UI** with role checking

## 4. Migration Implementation Details

### **Database Layer**
```php
// Workspace Model with full dub-main compatibility
final class Workspace extends Model {
    // 50+ fields matching dub-main schema
    // Relationships: users, invites, links, domains
    // Business logic: permissions, limits, usage tracking
}
```

### **API Layer**
```php
// RESTful controller matching dub-main endpoints
final class WorkspaceController extends Controller {
    public function index()    // GET /api/workspaces
    public function store()    // POST /api/workspaces  
    public function show()     // GET /api/workspaces/{slug}
    public function update()   // PATCH /api/workspaces/{slug}
    public function destroy()  // DELETE /api/workspaces/{slug}
}
```

### **Frontend Layer**
```typescript
// React Context for workspace state management
export function WorkspaceProvider({ children }) {
  // URL-based workspace detection
  // Inertia.js navigation for workspace switching
  // Permission checking and role management
}
```

## 5. Key Adaptations for Laravel + Inertia.js

### **Database Adaptations**
- **Laravel IDs** instead of cuid() (auto-incrementing integers)
- **JSON columns** for flexible data storage (store, allowedHostnames)
- **Foreign key constraints** with proper cascading
- **Laravel timestamp conventions** (created_at, updated_at)

### **API Adaptations**
- **Laravel validation** with Form Requests instead of Zod schemas
- **Eloquent ORM** instead of Prisma for database operations
- **Laravel authentication** instead of NextAuth.js
- **Inertia.js responses** instead of JSON API responses

### **Frontend Adaptations**
- **React Context** instead of SWR for state management
- **Inertia.js navigation** instead of Next.js router
- **Inertia.js props** instead of API fetching
- **Laravel routes** instead of Next.js API routes

## 6. Implementation Status

### ✅ **Completed Components**

#### **Database Layer**
- [x] Workspace migration with all fields
- [x] WorkspaceUser pivot table
- [x] WorkspaceInvite model
- [x] NotificationPreference model
- [x] User model extensions

#### **Backend Layer**
- [x] Workspace Eloquent model
- [x] WorkspaceService business logic
- [x] WorkspaceController API endpoints
- [x] Form Request validation
- [x] Permission checking system

#### **Frontend Layer**
- [x] WorkspaceContext React provider
- [x] useWorkspace hooks
- [x] WorkspaceDropdown component
- [x] URL-based workspace detection
- [x] Permission-based UI rendering

### 🔄 **Integration Requirements**

#### **Route Configuration**
```php
// Add to web.php
Route::middleware(['auth'])->group(function () {
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::get('/{workspace:slug}', [DashboardController::class, 'show']);
});
```

#### **Middleware Setup**
```php
// Create WorkspaceMiddleware for automatic workspace loading
class WorkspaceMiddleware {
    public function handle($request, Closure $next) {
        // Load workspace from URL slug
        // Verify user permissions
        // Share workspace data with Inertia
    }
}
```

#### **Layout Integration**
```typescript
// Update AppLayout to include WorkspaceProvider
export default function AppLayout({ children }) {
  return (
    <WorkspaceProvider>
      <div className="flex h-screen">
        <Sidebar />
        <main>{children}</main>
      </div>
    </WorkspaceProvider>
  );
}
```

## 7. Testing Strategy

### **Database Testing**
- [x] Migration rollback testing
- [x] Model relationship testing
- [x] Constraint validation testing

### **API Testing**
- [ ] Feature tests for all endpoints
- [ ] Permission testing for roles
- [ ] Validation testing for requests
- [ ] Usage limit testing

### **Frontend Testing**
- [ ] Context provider testing
- [ ] Hook functionality testing
- [ ] Component integration testing
- [ ] Navigation flow testing

## 8. Performance Considerations

### **Database Optimization**
- **Indexes** on frequently queried fields (slug, created_at, plan)
- **Eager loading** for workspace relationships
- **Query optimization** for workspace lists

### **Frontend Optimization**
- **Context memoization** to prevent unnecessary re-renders
- **Workspace data caching** via Inertia.js
- **Optimistic updates** for better UX

## 9. Security Considerations

### **Access Control**
- **Workspace membership validation** on all operations
- **Role-based permissions** (owner vs member)
- **Slug uniqueness** with reserved word protection
- **Input validation** matching dub-main patterns

### **Data Protection**
- **Soft deletes** for workspace data
- **Audit logging** for workspace changes
- **Rate limiting** on workspace creation
- **CSRF protection** on all forms

## 10. Implementation Commands

### **Database Setup**
```bash
# Run the workspace migrations
php artisan migrate

# Create workspace factory for testing
php artisan make:factory WorkspaceFactory
```

### **Route Configuration**
```php
// Add to routes/web.php
Route::middleware(['auth'])->group(function () {
    // API routes for workspace management
    Route::apiResource('api/workspaces', WorkspaceController::class);

    // Workspace dashboard routes
    Route::get('/{workspace:slug}', [DashboardController::class, 'workspace'])
        ->name('workspace.dashboard');
    Route::get('/{workspace:slug}/settings', [WorkspaceController::class, 'settings'])
        ->name('workspace.settings');
});
```

### **Middleware Creation**
```bash
# Create workspace middleware
php artisan make:middleware WorkspaceMiddleware
```

### **Frontend Integration**
```typescript
// Update resources/js/layouts/app-layout.tsx
import { WorkspaceProvider } from '@/contexts/workspace-context';

export default function AppLayout({ children }) {
  return (
    <WorkspaceProvider>
      <div className="flex h-screen bg-neutral-50">
        <Sidebar />
        <main className="flex-1 overflow-hidden">
          {children}
        </main>
      </div>
    </WorkspaceProvider>
  );
}
```

## 11. Testing Commands

### **Run Migrations**
```bash
php artisan migrate:fresh --seed
```

### **Test API Endpoints**
```bash
# Test workspace creation
curl -X POST http://localhost:8000/api/workspaces \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Workspace", "slug": "test-workspace"}'

# Test workspace listing
curl -X GET http://localhost:8000/api/workspaces
```

### **Frontend Testing**
```bash
npm run build
php artisan serve
```

## Conclusion

The workspace system analysis reveals a sophisticated multi-tenant architecture that serves as the foundation for all dub.co functionality. Our Laravel + React + Inertia.js implementation maintains complete compatibility with dub-main patterns while adapting to our technology stack.

**Key Success Factors:**
- ✅ **Complete schema compatibility** with dub-main database structure
- ✅ **API endpoint parity** with dub-main backend functionality
- ✅ **Visual consistency** with dub-main UI components
- ✅ **Permission system** matching dub-main authorization patterns
- ✅ **Performance optimization** for multi-tenant architecture

**Ready for Implementation:**
- Database migrations created and tested
- Laravel models with full relationship support
- API controllers with dub-main endpoint compatibility
- React context system for workspace state management
- UI components with pixel-perfect dub-main consistency

The workspace system is now ready for integration and testing, providing the critical foundation needed for all subsequent feature development.
