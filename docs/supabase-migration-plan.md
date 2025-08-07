# Comprehensive SQLite to Supabase PostgreSQL Migration Plan

## 🎯 **Migration Overview**

**Current State**: Laravel application with SQLite database (`database/database.sqlite`)
**Target State**: Laravel application with Supabase PostgreSQL database
**Available Supabase Project**: `hlipluswsrhzfkjixowk` (Bimiup.com, eu-central-1)

## 📊 **Pre-Migration Assessment**

### **Current Database Structure**
- **Database**: SQLite (`database/database.sqlite`)
- **Tables**: 19 migration files (users, workspaces, domains, links, etc.)
- **Pending Migrations**: 4 critical migrations for dub-main compatibility
- **Data**: Existing user and workspace data to preserve

### **Target Supabase Setup**
- **Project ID**: `hlipluswsrhzfkjixowk` (or create new project)
- **Region**: eu-central-1
- **Database**: PostgreSQL 17.4.1
- **Connection**: Pooled connection with SSL

## 🚀 **Phase 1: Pre-Migration Setup (Day 1)**

### **Step 1.1: Backup Current Database**
```bash
# Create backup of current SQLite database
cp database/database.sqlite database/database.sqlite.backup.$(date +%Y%m%d_%H%M%S)

# Export current data for verification
sqlite3 database/database.sqlite ".dump" > database/sqlite_backup.sql
```

### **Step 1.2: Verify Current Data**
```bash
# Check current data counts
sqlite3 database/database.sqlite "
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'workspaces', COUNT(*) FROM workspaces
UNION ALL
SELECT 'domains', COUNT(*) FROM domains
UNION ALL
SELECT 'links', COUNT(*) FROM links;
"
```

### **Step 1.3: Install PostgreSQL Tools**
```bash
# macOS (if not already installed)
brew install postgresql

# Verify installation
pg_dump --version
psql --version
```

## 🔧 **Phase 2: Supabase Configuration (Day 1)**

### **Step 2.1: Get Supabase Connection Details**
```bash
# Get connection string for existing project
# Replace with your actual project details
export SUPABASE_DB_URL="postgresql://postgres.hlipluswsrhzfkjixowk:[YOUR-PASSWORD]@aws-0-eu-central-1.pooler.supabase.com:5432/postgres"
```

### **Step 2.2: Update Laravel Configuration**
Update `.env` file:
```env
# Database Configuration
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres.hlipluswsrhzfkjixowk:[YOUR-PASSWORD]@aws-0-eu-central-1.pooler.supabase.com:5432/postgres

# PostgreSQL specific settings
DB_HOST=aws-0-eu-central-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.hlipluswsrhzfkjixowk
DB_PASSWORD=[YOUR-PASSWORD]

# Optional: Use separate schema for Laravel
DB_SCHEMA=laravel
```

### **Step 2.3: Update Database Configuration**
Update `config/database.php`:
```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'postgres'),
    'username' => env('DB_USERNAME', 'postgres'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => env('DB_SCHEMA', 'public'),
    'sslmode' => 'require',
],
```

## 📦 **Phase 3: Schema Migration (Day 2)**

### **Step 3.1: Test PostgreSQL Connection**
```bash
# Test connection to Supabase
php artisan tinker
>>> DB::connection('pgsql')->getPdo();
>>> DB::connection('pgsql')->select('SELECT version()');
```

### **Step 3.2: Run Fresh Migrations on Supabase**
```bash
# Set PostgreSQL as default connection
export DB_CONNECTION=pgsql

# Run all migrations on Supabase (fresh install)
php artisan migrate:fresh --force

# Verify tables were created
php artisan tinker
>>> DB::connection('pgsql')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename NOT LIKE 'pg_%'");
```

### **Step 3.3: Apply Critical Pending Migrations**
```bash
# Apply the critical migrations we created for dub-main compatibility
php artisan migrate --path=database/migrations/2025_08_06_070045_resolve_project_workspace_duplication.php
php artisan migrate --path=database/migrations/2025_08_06_070112_add_missing_fields_to_workspaces_table.php
php artisan migrate --path=database/migrations/2025_08_06_070147_create_tags_table.php
php artisan migrate --path=database/migrations/2025_08_06_070209_create_link_tags_table.php
```

## 💾 **Phase 4: Data Migration (Day 2-3)**

### **Step 4.1: Export SQLite Data**
```bash
# Create data export script
cat > database/export_sqlite_data.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => 'database/database.sqlite',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Export users
$users = Capsule::table('users')->get();
file_put_contents('database/export_users.json', json_encode($users, JSON_PRETTY_PRINT));

// Export workspaces
$workspaces = Capsule::table('workspaces')->get();
file_put_contents('database/export_workspaces.json', json_encode($workspaces, JSON_PRETTY_PRINT));

// Export domains
$domains = Capsule::table('domains')->get();
file_put_contents('database/export_domains.json', json_encode($domains, JSON_PRETTY_PRINT));

// Export links
$links = Capsule::table('links')->get();
file_put_contents('database/export_links.json', json_encode($links, JSON_PRETTY_PRINT));

echo "Data exported successfully!\n";
EOF

php database/export_sqlite_data.php
```

### **Step 4.2: Import Data to Supabase**
```bash
# Create data import script
cat > database/import_to_supabase.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

// Set environment to use PostgreSQL
putenv('DB_CONNECTION=pgsql');

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Import users
$users = json_decode(file_get_contents('database/export_users.json'), true);
foreach ($users as $user) {
    DB::table('users')->insert($user);
}
echo "Users imported: " . count($users) . "\n";

// Import workspaces
$workspaces = json_decode(file_get_contents('database/export_workspaces.json'), true);
foreach ($workspaces as $workspace) {
    DB::table('workspaces')->insert($workspace);
}
echo "Workspaces imported: " . count($workspaces) . "\n";

// Import domains
$domains = json_decode(file_get_contents('database/export_domains.json'), true);
foreach ($domains as $domain) {
    DB::table('domains')->insert($domain);
}
echo "Domains imported: " . count($domains) . "\n";

// Import links
$links = json_decode(file_get_contents('database/export_links.json'), true);
foreach ($links as $link) {
    DB::table('links')->insert($link);
}
echo "Links imported: " . count($links) . "\n";

echo "All data imported successfully!\n";
EOF

php database/import_to_supabase.php
```

## ⚙️ **Phase 5: Application Updates (Day 3)**

### **Step 5.1: Update Environment Configuration**
```bash
# Update .env to use PostgreSQL as default
sed -i '' 's/DB_CONNECTION=sqlite/DB_CONNECTION=pgsql/' .env

# Clear configuration cache
php artisan config:clear
php artisan cache:clear
```

### **Step 5.2: Handle PostgreSQL-Specific Changes**
```php
// Update any SQLite-specific code in your models or queries
// Example: Replace AUTOINCREMENT with SERIAL
// Example: Update date/time handling for PostgreSQL
```

### **Step 5.3: Update Model Configurations**
```php
// In your models, ensure proper casting for PostgreSQL
// Example: JSON columns should use 'json' cast instead of 'array'
protected $casts = [
    'preferences' => 'json',  // PostgreSQL native JSON
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

## 🧪 **Phase 6: Testing & Verification (Day 4)**

### **Step 6.1: Verify Data Integrity**
```bash
# Compare record counts
php artisan tinker
>>> DB::table('users')->count();
>>> DB::table('workspaces')->count();
>>> DB::table('domains')->count();
>>> DB::table('links')->count();
```

### **Step 6.2: Test Application Functionality**
```bash
# Start the application
php artisan serve --port=8001

# Test key functionality:
# 1. User authentication
# 2. Workspace switching
# 3. Domain management
# 4. Link creation
# 5. Database relationships
```

### **Step 6.3: Test Workspace-Aware Authentication**
```bash
# Test the authentication flow
curl -X POST http://127.0.0.1:8001/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Verify workspace redirection works
curl -X GET http://127.0.0.1:8001/onboarding \
  -H "Cookie: laravel_session=..."
```

## 🧹 **Phase 7: Cleanup (Day 4)**

### **Step 7.1: Remove SQLite Files**
```bash
# After successful migration and testing
rm database/database.sqlite
rm database/export_*.json
rm database/export_sqlite_data.php
rm database/import_to_supabase.php
```

### **Step 7.2: Update Documentation**
```bash
# Update README.md with new database setup instructions
# Update deployment documentation
# Update environment variable documentation
```

## 🚨 **Rollback Procedures**

### **Emergency Rollback to SQLite**
```bash
# Restore SQLite database
cp database/database.sqlite.backup.* database/database.sqlite

# Revert .env configuration
sed -i '' 's/DB_CONNECTION=pgsql/DB_CONNECTION=sqlite/' .env

# Clear caches
php artisan config:clear
php artisan cache:clear

# Restart application
php artisan serve
```

### **Partial Rollback (Keep Supabase, Fix Issues)**
```bash
# Drop all tables and re-migrate
php artisan migrate:fresh --force

# Re-import data
php database/import_to_supabase.php
```

## ✅ **Success Criteria Checklist**

### **Database Migration**
- [ ] Supabase PostgreSQL connection established
- [ ] All tables created successfully
- [ ] All data migrated without loss
- [ ] Foreign key constraints working
- [ ] Indexes created properly

### **Application Functionality**
- [ ] User authentication working
- [ ] Workspace-aware authentication functional
- [ ] All CRUD operations working
- [ ] Relationships loading correctly
- [ ] No database-related errors

### **Performance & Security**
- [ ] Database queries performing well
- [ ] SSL connection established
- [ ] Connection pooling working
- [ ] No sensitive data exposed

## 📋 **Migration Timeline**

| **Day** | **Phase** | **Duration** | **Tasks** |
|---------|-----------|--------------|-----------|
| Day 1 | Setup & Config | 4 hours | Backup, Supabase setup, Laravel config |
| Day 2 | Schema & Data | 6 hours | Migrate schema, export/import data |
| Day 3 | App Updates | 4 hours | Update code, handle PostgreSQL specifics |
| Day 4 | Testing & Cleanup | 4 hours | Verify functionality, cleanup files |

**Total Estimated Time**: 18 hours over 4 days

## 🔧 **Tools & Dependencies**

### **Required Tools**
- PostgreSQL client tools (`pg_dump`, `psql`)
- PHP with PostgreSQL extension
- Composer
- Supabase account access

### **Laravel Packages**
```bash
# Ensure PostgreSQL driver is available
composer require doctrine/dbal  # For schema operations
```

## 🎯 **Next Steps**

After reviewing this plan, we'll need to:

1. **Confirm Supabase Project**: Use existing `hlipluswsrhzfkjixowk` or create new project
2. **Get Database Credentials**: Obtain the actual connection string and password
3. **Execute Migration**: Follow the phase-by-phase plan
4. **Verify Compatibility**: Ensure all dub-main schema requirements are met

This comprehensive migration plan ensures a safe, systematic transition from SQLite to Supabase PostgreSQL while preserving all your existing data and maintaining the workspace-aware authentication system.
