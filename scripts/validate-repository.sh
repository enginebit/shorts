#!/bin/bash

# Repository Validation Script for Dub.co to Laravel Migration Project
# This script validates the repository setup and GitHub integration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
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

print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

# Validation counters
PASSED=0
FAILED=0
WARNINGS=0

# Function to validate file exists
validate_file() {
    local file=$1
    local description=$2
    
    if [ -f "$file" ]; then
        print_success "$description exists: $file"
        ((PASSED++))
        return 0
    else
        print_error "$description missing: $file"
        ((FAILED++))
        return 1
    fi
}

# Function to validate directory exists
validate_directory() {
    local dir=$1
    local description=$2
    
    if [ -d "$dir" ]; then
        print_success "$description exists: $dir"
        ((PASSED++))
        return 0
    else
        print_error "$description missing: $dir"
        ((FAILED++))
        return 1
    fi
}

# Function to validate YAML syntax
validate_yaml() {
    local file=$1
    local description=$2

    if command -v python3 &> /dev/null; then
        if python3 -c "import yaml" 2>/dev/null; then
            if python3 -c "import yaml; yaml.safe_load(open('$file'))" 2>/dev/null; then
                print_success "$description has valid YAML syntax"
                ((PASSED++))
            else
                print_error "$description has invalid YAML syntax"
                ((FAILED++))
            fi
        else
            print_warning "PyYAML not available, skipping YAML validation for $description"
            ((WARNINGS++))
        fi
    else
        print_warning "Python3 not available, skipping YAML validation for $description"
        ((WARNINGS++))
    fi
}

echo "🔍 Validating Dub.co to Laravel Migration Repository Setup..."

# =============================================================================
# Core Project Files
# =============================================================================
print_header "Core Project Files"

validate_file "README.md" "Project README"
validate_file "CONTRIBUTING.md" "Contributing guidelines"
validate_file "LICENSE" "License file"
validate_file "package.json" "Root package.json"
validate_file ".gitignore" "Git ignore file"

# =============================================================================
# Documentation Structure
# =============================================================================
print_header "Documentation Structure"

validate_file "docs/PRD-Dub-Migration.md" "Product Requirements Document"
validate_directory "docs/architecture" "Architecture documentation directory"
validate_file "docs/architecture/README.md" "Architecture documentation index"
validate_directory "docs/api" "API documentation directory"
validate_directory "docs/deployment" "Deployment documentation directory"

# =============================================================================
# GitHub Integration
# =============================================================================
print_header "GitHub Integration"

validate_directory ".github" "GitHub configuration directory"
validate_directory ".github/ISSUE_TEMPLATE" "GitHub issue templates directory"
validate_directory ".github/workflows" "GitHub workflows directory"

# Issue Templates
validate_file ".github/ISSUE_TEMPLATE/config.yml" "Issue template configuration"
validate_file ".github/ISSUE_TEMPLATE/phase-template.yml" "Phase issue template"
validate_file ".github/ISSUE_TEMPLATE/epic-template.yml" "Epic issue template"
validate_file ".github/ISSUE_TEMPLATE/task-template.yml" "Task issue template"

# Workflows
validate_file ".github/workflows/project-automation.yml" "Project automation workflow"

# =============================================================================
# YAML Syntax Validation
# =============================================================================
print_header "YAML Syntax Validation"

if [ -f ".github/ISSUE_TEMPLATE/config.yml" ]; then
    validate_yaml ".github/ISSUE_TEMPLATE/config.yml" "Issue template config"
fi

if [ -f ".github/ISSUE_TEMPLATE/phase-template.yml" ]; then
    validate_yaml ".github/ISSUE_TEMPLATE/phase-template.yml" "Phase template"
fi

if [ -f ".github/ISSUE_TEMPLATE/epic-template.yml" ]; then
    validate_yaml ".github/ISSUE_TEMPLATE/epic-template.yml" "Epic template"
fi

if [ -f ".github/ISSUE_TEMPLATE/task-template.yml" ]; then
    validate_yaml ".github/ISSUE_TEMPLATE/task-template.yml" "Task template"
fi

if [ -f ".github/workflows/project-automation.yml" ]; then
    validate_yaml ".github/workflows/project-automation.yml" "Project automation workflow"
fi

# =============================================================================
# Scripts and Tools
# =============================================================================
print_header "Scripts and Tools"

validate_directory "scripts" "Scripts directory"
validate_file "scripts/setup-project.sh" "Project setup script"
validate_file "scripts/generate-github-issues.md" "GitHub issues generation guide"
validate_file "scripts/validate-repository.sh" "Repository validation script"

# Check script permissions
if [ -f "scripts/setup-project.sh" ]; then
    if [ -x "scripts/setup-project.sh" ]; then
        print_success "Setup script is executable"
        ((PASSED++))
    else
        print_warning "Setup script is not executable (run: chmod +x scripts/setup-project.sh)"
        ((WARNINGS++))
    fi
fi

# =============================================================================
# Augment Rules
# =============================================================================
print_header "Augment Rules"

validate_directory ".augment/rules" "Augment rules directory"
validate_file ".augment/rules/coding-standards.md" "Coding standards"
validate_file ".augment/rules/dub-reference.md" "Dub reference patterns"
validate_file ".augment/rules/review-checklist.md" "Review checklist"

# =============================================================================
# Git Repository Validation
# =============================================================================
print_header "Git Repository"

if [ -d ".git" ]; then
    print_success "Git repository initialized"
    ((PASSED++))
    
    # Check if there are commits
    if git rev-parse HEAD >/dev/null 2>&1; then
        print_success "Repository has commits"
        ((PASSED++))
        
        # Check current branch
        BRANCH=$(git branch --show-current)
        print_info "Current branch: $BRANCH"
        
        # Check if remote is configured
        if git remote -v | grep -q origin; then
            REMOTE_URL=$(git remote get-url origin)
            print_success "Remote origin configured: $REMOTE_URL"
            ((PASSED++))
        else
            print_warning "No remote origin configured"
            ((WARNINGS++))
        fi
    else
        print_warning "No commits in repository yet"
        ((WARNINGS++))
    fi
else
    print_error "Git repository not initialized"
    ((FAILED++))
fi

# =============================================================================
# .gitignore Validation
# =============================================================================
print_header ".gitignore Validation"

if [ -f ".gitignore" ]; then
    # Check for dub-main exclusion
    if grep -q "dub-main" ".gitignore"; then
        print_success "dub-main directory excluded from version control"
        ((PASSED++))
    else
        print_error "dub-main directory not excluded in .gitignore"
        ((FAILED++))
    fi
    
    # Check for common exclusions
    EXCLUSIONS=("node_modules/" "vendor/" ".env" "*.log" ".DS_Store")
    for exclusion in "${EXCLUSIONS[@]}"; do
        if grep -q "$exclusion" ".gitignore"; then
            print_success "Standard exclusion found: $exclusion"
            ((PASSED++))
        else
            print_warning "Standard exclusion missing: $exclusion"
            ((WARNINGS++))
        fi
    done
else
    print_error ".gitignore file missing"
    ((FAILED++))
fi

# =============================================================================
# Content Validation
# =============================================================================
print_header "Content Validation"

# Check README content
if [ -f "README.md" ]; then
    if grep -q "Dub.co to Laravel Migration" "README.md"; then
        print_success "README contains project title"
        ((PASSED++))
    else
        print_warning "README missing project title"
        ((WARNINGS++))
    fi
    
    if grep -q "Technology Stack Migration" "README.md"; then
        print_success "README contains technology stack information"
        ((PASSED++))
    else
        print_warning "README missing technology stack section"
        ((WARNINGS++))
    fi
fi

# Check PRD content
if [ -f "docs/PRD-Dub-Migration.md" ]; then
    if grep -q "Phase 1.*Backend Foundation" "docs/PRD-Dub-Migration.md"; then
        print_success "PRD contains development phases"
        ((PASSED++))
    else
        print_warning "PRD missing development phases"
        ((WARNINGS++))
    fi
fi

# =============================================================================
# Summary
# =============================================================================
print_header "Validation Summary"

TOTAL=$((PASSED + FAILED + WARNINGS))

echo -e "\n📊 Validation Results:"
echo -e "  ${GREEN}✅ Passed: $PASSED${NC}"
echo -e "  ${RED}❌ Failed: $FAILED${NC}"
echo -e "  ${YELLOW}⚠️  Warnings: $WARNINGS${NC}"
echo -e "  📋 Total Checks: $TOTAL"

if [ $FAILED -eq 0 ]; then
    echo -e "\n🎉 ${GREEN}Repository validation completed successfully!${NC}"
    
    if [ $WARNINGS -gt 0 ]; then
        echo -e "   ${YELLOW}Note: $WARNINGS warnings found - review recommended${NC}"
    fi
    
    echo -e "\n📋 Next Steps:"
    echo -e "  1. Create GitHub repository: https://github.com/new"
    echo -e "  2. Add remote: git remote add origin https://github.com/makafeli/dub-laravel-migration.git"
    echo -e "  3. Push to GitHub: git push -u origin main"
    echo -e "  4. Set up GitHub Issues using scripts/generate-github-issues.md"
    echo -e "  5. Configure GitHub Projects for task management"
    
    exit 0
else
    echo -e "\n💥 ${RED}Repository validation failed with $FAILED errors${NC}"
    echo -e "   ${YELLOW}Please fix the errors above before proceeding${NC}"
    exit 1
fi
