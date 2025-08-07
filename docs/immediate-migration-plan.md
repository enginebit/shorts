# Immediate Migration Plan - Critical Schema Fixes

## 🚨 **CRITICAL ISSUE: Project/Workspace Duplication**

### Problem Analysis
Our Laravel implementation has **both** `projects` and `workspaces` tables, but dub-main only uses `Project` as the primary entity. This creates confusion and relationship conflicts.

### **Decision Required**: Choose Primary Entity
**Option A**: Use `workspaces` as primary (rename to match dub-main `Project`)
**Option B**: Use `projects` as primary (update all workspace references)

**Recommendation**: **Option A** - Keep `workspaces` as primary since our authentication system is built around it.

## Phase 1: Critical Foundation Fixes (This Week)

### **1. Resolve Entity Duplication**
```bash
# Remove the duplicate projects table and migrate data if needed
php artisan make:migration remove_duplicate_projects_table
```

### **2. Add Missing Workspace Fields**
```bash
# Add billing and feature flag fields to workspaces
php artisan make:migration add_billing_and_features_to_workspaces_table
```

**Migration Content**:
```php
Schema::table('workspaces', function (Blueprint $table) {
    // Billing fields
    $table->string('stripe_id')->nullable()->unique()->after('plan');
    $table->integer('billing_cycle_start')->default(1)->after('stripe_id');
    
    // Feature limits
    $table->integer('tags_limit')->default(5)->after('users_limit');
    $table->integer('webhooks_limit')->default(5)->after('tags_limit');
    $table->integer('api_limit')->default(60)->after('webhooks_limit');
    
    // Feature flags
    $table->boolean('conversion_enabled')->default(false)->after('partners_enabled');
    $table->boolean('webhooks_enabled')->default(true)->after('conversion_enabled');
    
    // Add indexes
    $table->index('stripe_id');
});
```

### **3. Enhance Links Table**
```bash
# Add advanced link features
php artisan make:migration add_advanced_features_to_links_table
```

**Migration Content**:
```php
Schema::table('links', function (Blueprint $table) {
    // Security features
    $table->string('password')->nullable()->after('clicks');
    
    // Advanced features
    $table->boolean('proxy')->default(false)->after('password');
    $table->boolean('rewrite')->default(false)->after('proxy');
    $table->timestamp('expires_at')->nullable()->after('rewrite');
    
    // Mobile deep linking
    $table->text('ios')->nullable()->after('expires_at');
    $table->text('android')->nullable()->after('ios');
    
    // Geographic targeting (JSON)
    $table->json('geo')->nullable()->after('android');
    
    // Organization
    $table->string('tag_id')->nullable()->after('folder_id');
    $table->string('folder_id')->nullable()->after('user_id');
    
    // Add indexes
    $table->index('expires_at');
    $table->index('tag_id');
    $table->index('folder_id');
});
```

### **4. Create Tags System**
```bash
# Create tags table
php artisan make:migration create_tags_table
```

**Migration Content**:
```php
Schema::create('tags', function (Blueprint $table) {
    $table->string('id')->primary(); // CUID format
    $table->string('name');
    $table->string('color')->default('#8B5CF6'); // Default purple
    $table->string('workspace_id'); // Reference to workspaces
    $table->timestamps();
    
    // Constraints
    $table->unique(['workspace_id', 'name']); // Unique per workspace
    $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
    
    // Indexes
    $table->index('workspace_id');
});
```

```bash
# Create link-tag pivot table
php artisan make:migration create_link_tags_table
```

**Migration Content**:
```php
Schema::create('link_tags', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->string('link_id');
    $table->string('tag_id');
    $table->timestamps();
    
    // Relationships
    $table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
    $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
    
    // Constraints
    $table->unique(['link_id', 'tag_id']);
    
    // Indexes
    $table->index('tag_id');
});
```

### **5. Create Folders System**
```bash
# Create folders table
php artisan make:migration create_folders_table
```

**Migration Content**:
```php
Schema::create('folders', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->string('name');
    $table->string('workspace_id');
    $table->timestamps();
    
    // Relationships
    $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
    
    // Constraints
    $table->unique(['workspace_id', 'name']);
    
    // Indexes
    $table->index('workspace_id');
});
```

## Phase 2: Essential Features (Next Week)

### **6. Create UTM Templates**
```bash
php artisan make:migration create_utm_templates_table
```

### **7. Basic Analytics Foundation**
```bash
php artisan make:migration create_link_events_table
```

### **8. Webhook System**
```bash
php artisan make:migration create_webhooks_table
php artisan make:migration create_link_webhooks_table
```

## Execution Order (Critical Path)

### **Day 1: Entity Resolution**
1. ✅ **Backup database**
2. 🔧 **Remove duplicate projects table**
3. 🔧 **Update all project references to workspace**

### **Day 2: Core Table Enhancements**
1. 🔧 **Add billing fields to workspaces**
2. 🔧 **Add advanced features to links**
3. ✅ **Test workspace authentication**

### **Day 3: Organization System**
1. 🔧 **Create tags table**
2. 🔧 **Create link_tags pivot**
3. 🔧 **Create folders table**
4. ✅ **Update Eloquent models**

### **Day 4: Model Relationships**
1. 🔧 **Add tag relationships to models**
2. 🔧 **Add folder relationships to models**
3. ✅ **Test all relationships**

### **Day 5: Integration Testing**
1. ✅ **Test workspace-aware authentication**
2. ✅ **Test link creation with tags/folders**
3. ✅ **Verify all database queries work**

## Data Migration Considerations

### **Project → Workspace Migration**
If we have existing data in the `projects` table:

```php
// Migration to move project data to workspaces
public function up()
{
    // Copy project data to workspaces if needed
    DB::statement('INSERT INTO workspaces (id, name, slug, ...) SELECT id, name, slug, ... FROM projects');
    
    // Update foreign key references
    DB::statement('UPDATE domains SET workspace_id = project_id WHERE project_id IS NOT NULL');
    DB::statement('UPDATE links SET workspace_id = project_id WHERE project_id IS NOT NULL');
    
    // Drop the projects table
    Schema::drop('projects');
}
```

### **Foreign Key Updates**
Update all references from `project_id` to `workspace_id`:
- ✅ `domains.project_id` → Already correct (keep as is per dub-main schema)
- ✅ `links.project_id` → Already correct (keep as is per dub-main schema)

## Risk Assessment

### **🔴 High Risk**
- **Data Loss**: Dropping projects table without proper migration
- **Relationship Breaks**: Foreign key constraint violations
- **Authentication Failure**: Workspace context breaking

### **🟡 Medium Risk**
- **Performance Impact**: Adding indexes during migration
- **Model Sync Issues**: Eloquent relationships out of sync
- **Frontend Breaks**: React components expecting different data structure

### **🟢 Low Risk**
- **New Table Creation**: Tags, folders, webhooks are additive
- **Field Addition**: New columns with defaults are safe
- **Index Addition**: Can be done online

## Success Criteria

### **✅ Phase 1 Complete When:**
1. **Single Entity**: Only workspaces table exists (no projects duplication)
2. **Enhanced Links**: All advanced link features available
3. **Organization**: Tags and folders system functional
4. **Authentication**: Workspace-aware auth still working
5. **No Errors**: All database queries execute successfully

### **📊 Verification Commands**
```bash
# Test database structure
php artisan migrate:status

# Test model relationships
php artisan tinker
>>> App\Models\Workspace::with(['domains', 'links', 'tags'])->first()

# Test authentication flow
# Navigate to /login and verify workspace redirection works
```

## Next Steps After Phase 1

1. **Frontend Updates**: Update React components to use new tag/folder data
2. **API Endpoints**: Create CRUD endpoints for tags and folders
3. **UI Components**: Migrate tag and folder components from dub-main
4. **Analytics Foundation**: Implement basic click tracking
5. **Webhook System**: Add event notification capabilities

This migration plan prioritizes the critical schema fixes needed for a stable workspace-aware authentication system while laying the foundation for advanced features.
