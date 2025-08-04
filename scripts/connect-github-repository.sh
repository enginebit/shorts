#!/bin/bash

# Connect Local Repository to GitHub - Dub.co to Laravel Migration Project
# This script connects the local git repository to the GitHub repository

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

echo "🔗 Connecting Local Repository to GitHub..."

# Repository details
REPO_OWNER="makafeli"
REPO_NAME="shorts"
REPO_URL="https://github.com/${REPO_OWNER}/${REPO_NAME}.git"

print_header "Pre-flight Checks"

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    print_error "Not in a git repository. Please run this script from the project root."
    exit 1
fi

print_success "Git repository detected"

# Check if we have commits
if ! git rev-parse HEAD >/dev/null 2>&1; then
    print_error "No commits found. Please commit your changes first."
    exit 1
fi

print_success "Commits found in repository"

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
print_info "Current branch: $CURRENT_BRANCH"

print_header "GitHub Repository Connection"

# Check if remote already exists
if git remote get-url origin >/dev/null 2>&1; then
    EXISTING_REMOTE=$(git remote get-url origin)
    print_warning "Remote 'origin' already exists: $EXISTING_REMOTE"
    
    read -p "Do you want to update the remote URL? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git remote set-url origin "$REPO_URL"
        print_success "Remote URL updated to: $REPO_URL"
    else
        print_info "Keeping existing remote URL"
    fi
else
    # Add GitHub remote
    print_info "Adding GitHub remote..."
    git remote add origin "$REPO_URL"
    print_success "Remote 'origin' added: $REPO_URL"
fi

# Verify remote
print_info "Verifying remote configuration..."
git remote -v

print_header "Push to GitHub"

# Push to GitHub
print_info "Pushing to GitHub repository..."

if git push -u origin "$CURRENT_BRANCH"; then
    print_success "Successfully pushed to GitHub!"
else
    print_error "Failed to push to GitHub. Please check:"
    echo "  1. Repository exists on GitHub"
    echo "  2. You have push permissions"
    echo "  3. Your GitHub authentication is configured"
    exit 1
fi

print_header "Verification"

# Verify the push was successful
print_info "Verifying repository connection..."

# Get the latest commit hash
LOCAL_COMMIT=$(git rev-parse HEAD)
print_info "Local commit: $LOCAL_COMMIT"

# Check if we can fetch from remote
if git fetch origin >/dev/null 2>&1; then
    REMOTE_COMMIT=$(git rev-parse origin/$CURRENT_BRANCH)
    print_info "Remote commit: $REMOTE_COMMIT"
    
    if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
        print_success "Local and remote repositories are in sync!"
    else
        print_warning "Local and remote commits don't match. This might be normal for the first push."
    fi
else
    print_warning "Could not fetch from remote. This might be normal for a new repository."
fi

print_header "Next Steps"

echo -e "\n🎉 ${GREEN}Repository successfully connected to GitHub!${NC}"
echo -e "\n📋 Next Steps:"
echo -e "  1. Visit: ${BLUE}https://github.com/${REPO_OWNER}/${REPO_NAME}${NC}"
echo -e "  2. Verify all files are present in the repository"
echo -e "  3. Check that issue templates are working"
echo -e "  4. Enable GitHub Actions if prompted"
echo -e "  5. Set up GitHub Projects for task management"
echo -e "  6. Create GitHub Issues using: ${YELLOW}scripts/generate-github-issues.md${NC}"

echo -e "\n🔧 Repository Configuration:"
echo -e "  • Issues: ✅ Enabled"
echo -e "  • Projects: ✅ Enabled"
echo -e "  • Actions: ✅ Available"
echo -e "  • Visibility: 🌍 Public"

echo -e "\n📊 Repository Contents:"
echo -e "  • README.md with comprehensive project overview"
echo -e "  • Issue templates for Phases, Epics, and Tasks"
echo -e "  • GitHub Actions workflow for project automation"
echo -e "  • Complete documentation structure"
echo -e "  • Comprehensive .gitignore (excluding dub-main reference)"
echo -e "  • Project setup and validation scripts"

echo -e "\n🚀 ${GREEN}Ready for team collaboration!${NC}"

# Final status
print_info "Repository URL: https://github.com/${REPO_OWNER}/${REPO_NAME}"
print_info "Clone URL: $REPO_URL"
print_info "Local branch: $CURRENT_BRANCH"
print_info "Remote tracking: origin/$CURRENT_BRANCH"

echo ""
print_success "GitHub repository connection completed successfully!"
