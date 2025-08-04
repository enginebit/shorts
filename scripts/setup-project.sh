#!/bin/bash

# Dub.co to Laravel Migration Project Setup Script
# This script sets up the complete project structure and GitHub integration

set -e

echo "🚀 Setting up Dub.co to Laravel Migration Project..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "README.md" ] || [ ! -d ".github" ]; then
    print_error "Please run this script from the project root directory"
    exit 1
fi

print_info "Setting up project structure..."

# Create backend directory structure
mkdir -p backend/{app/{Http/{Controllers,Requests,Resources,Middleware},Models,Services,Actions,Data,Enums},database/{migrations,seeders,factories},routes,tests/{Feature,Unit},config,storage}

# Create frontend directory structure
mkdir -p frontend/resources/{js/{components/{ui,forms,layout,shared},pages,hooks,lib,types,constants},css,views}

# Create additional project directories
mkdir -p {docs/{architecture,api,deployment},scripts,tests/{e2e,performance}}

print_status "Project directory structure created"

# Create Laravel backend structure
print_info "Setting up Laravel backend structure..."

# Create basic Laravel files
touch backend/{artisan,composer.json,.env.example}
touch backend/app/Http/Controllers/.gitkeep
touch backend/app/Models/.gitkeep
touch backend/app/Services/.gitkeep
touch backend/database/migrations/.gitkeep
touch backend/routes/{web.php,api.php}
touch backend/tests/Feature/.gitkeep
touch backend/tests/Unit/.gitkeep

print_status "Laravel backend structure created"

# Create React frontend structure
print_info "Setting up React frontend structure..."

# Create basic frontend files
touch frontend/{package.json,vite.config.js,tailwind.config.js,tsconfig.json}
touch frontend/resources/js/{app.tsx,bootstrap.ts}
touch frontend/resources/js/components/ui/.gitkeep
touch frontend/resources/js/pages/.gitkeep
touch frontend/resources/css/app.css

print_status "React frontend structure created"

# Create documentation files
print_info "Creating documentation files..."

# Architecture documentation
touch docs/architecture/{system-overview.md,technology-stack.md,database-design.md,api-design.md}
touch docs/architecture/{authentication.md,analytics.md,partner-program.md,billing.md}
touch docs/architecture/{component-system.md,state-management.md,routing.md,design-system.md}
touch docs/architecture/{monitoring.md,security.md,performance.md}

# API documentation
touch docs/api/{README.md,authentication.md,links.md,analytics.md,domains.md,workspaces.md,partners.md}

# Deployment documentation
touch docs/deployment/{README.md,infrastructure.md,ci-cd.md,monitoring.md}

print_status "Documentation structure created"

# Create test files
print_info "Setting up test structure..."

touch tests/e2e/.gitkeep
touch tests/performance/.gitkeep

print_status "Test structure created"

# Create additional configuration files
print_info "Creating configuration files..."

# Docker files
touch {Dockerfile,docker-compose.yml,.dockerignore}

# CI/CD files
mkdir -p .github/workflows
touch .github/workflows/{ci.yml,cd.yml,tests.yml}

# Project configuration
touch {.gitignore,.editorconfig,phpcs.xml,phpunit.xml}

print_status "Configuration files created"

# Create package.json for root project
cat > package.json << 'EOF'
{
  "name": "dub-laravel-migration",
  "version": "1.0.0",
  "description": "Comprehensive migration of Dub.co architecture from Next.js to Laravel + React + Inertia.js",
  "private": true,
  "scripts": {
    "dev": "concurrently \"npm run dev:backend\" \"npm run dev:frontend\"",
    "dev:backend": "cd backend && php artisan serve",
    "dev:frontend": "cd frontend && npm run dev",
    "build": "cd frontend && npm run build",
    "test": "npm run test:backend && npm run test:frontend",
    "test:backend": "cd backend && php artisan test",
    "test:frontend": "cd frontend && npm run test",
    "lint": "npm run lint:backend && npm run lint:frontend",
    "lint:backend": "cd backend && ./vendor/bin/phpcs",
    "lint:frontend": "cd frontend && npm run lint",
    "setup": "npm run setup:backend && npm run setup:frontend",
    "setup:backend": "cd backend && composer install && cp .env.example .env && php artisan key:generate",
    "setup:frontend": "cd frontend && npm install"
  },
  "devDependencies": {
    "concurrently": "^8.2.2"
  },
  "keywords": [
    "laravel",
    "react",
    "inertiajs",
    "url-shortener",
    "dub",
    "migration"
  ],
  "author": "Yasin Boelhouwer",
  "license": "MIT"
}
EOF

print_status "Root package.json created"

# Create .gitignore
cat > .gitignore << 'EOF'
# Dependencies
node_modules/
vendor/

# Environment files
.env
.env.local
.env.production

# Build outputs
/backend/public/build
/frontend/dist
/backend/storage/logs/*.log

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Laravel specific
/backend/bootstrap/cache/*.php
/backend/storage/framework/cache/*
/backend/storage/framework/sessions/*
/backend/storage/framework/views/*

# Testing
/backend/coverage
/frontend/coverage

# Temporary files
*.tmp
*.temp
EOF

print_status ".gitignore created"

# Create CONTRIBUTING.md
cat > CONTRIBUTING.md << 'EOF'
# Contributing to Dub.co Laravel Migration

Thank you for your interest in contributing to the Dub.co to Laravel migration project!

## Development Process

### 1. Backend-First Approach
- Always implement Laravel backend functionality before frontend components
- Ensure API endpoints are stable and tested before building UI
- Follow the established phase structure (Backend → Frontend → Integration)

### 2. Reference Compliance
- Always reference the dub-main repository for patterns and implementations
- Maintain visual and functional parity with the original Dub.co
- Document any adaptations made for Laravel + Inertia.js

### 3. Code Standards
- Follow the coding standards defined in `.augment/rules/`
- Use the established component patterns from dub-main
- Maintain TypeScript strict mode and PHP strict types

## Getting Started

1. **Fork the repository**
2. **Clone your fork**: `git clone https://github.com/YOUR_USERNAME/dub-laravel-migration.git`
3. **Install dependencies**: `npm run setup`
4. **Create a feature branch**: `git checkout -b feature/your-feature-name`
5. **Make your changes** following the guidelines
6. **Test your changes**: `npm run test`
7. **Submit a pull request**

## Pull Request Process

1. **Update documentation** if you've made architectural changes
2. **Add tests** for new functionality
3. **Ensure all tests pass** and code follows standards
4. **Reference related issues** in your PR description
5. **Request review** from appropriate team members

## Issue Guidelines

- Use the provided issue templates for consistency
- Include Augment task UUIDs when applicable
- Reference dub-main components and patterns
- Provide clear acceptance criteria and implementation details

## Questions?

- Check the [documentation](docs/)
- Search existing [issues](https://github.com/makafeli/dub-laravel-migration/issues)
- Start a [discussion](https://github.com/makafeli/dub-laravel-migration/discussions)
EOF

print_status "CONTRIBUTING.md created"

# Create LICENSE
cat > LICENSE << 'EOF'
MIT License

Copyright (c) 2024 Yasin Boelhouwer

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOF

print_status "LICENSE created"

print_info "Project setup complete! 🎉"
print_info ""
print_info "Next steps:"
print_info "1. Initialize git repository: git init"
print_info "2. Add files: git add ."
print_info "3. Initial commit: git commit -m 'Initial project setup'"
print_info "4. Create GitHub repository and push"
print_info "5. Set up GitHub Issues using scripts/generate-github-issues.md"
print_info "6. Configure GitHub Projects for task management"
print_info ""
print_info "For development:"
print_info "1. Run: npm run setup (install dependencies)"
print_info "2. Run: npm run dev (start development servers)"
print_info "3. Visit: http://localhost:8000 (Laravel) and http://localhost:5173 (Vite)"

echo ""
print_status "Setup completed successfully! Ready to start the migration project."
EOF
