# GitHub Repository Setup Guide

This guide provides step-by-step instructions for creating and configuring the GitHub repository for the Dub.co to Laravel migration project.

## Step 1: Create GitHub Repository

### Manual Creation (Recommended)
1. **Go to GitHub**: Visit [https://github.com/new](https://github.com/new)
2. **Repository Details**:
   - **Repository name**: `dub-laravel-migration`
   - **Description**: `Comprehensive migration of Dub.co architecture from Next.js to Laravel + React + Inertia.js stack, maintaining functional and visual parity`
   - **Visibility**: Public ✅
   - **Initialize repository**: ❌ (Do NOT check - we have existing files)
   - **Add .gitignore**: ❌ (We already have one)
   - **Choose a license**: ❌ (We already have MIT license)

3. **Click "Create repository"**

## Step 2: Connect Local Repository

```bash
# Add GitHub remote
git remote add origin https://github.com/makafeli/dub-laravel-migration.git

# Verify remote
git remote -v

# Push to GitHub
git push -u origin main
```

## Step 3: Configure Repository Settings

### Repository Settings
1. **Go to Settings tab** in your GitHub repository
2. **General Settings**:
   - ✅ Issues enabled
   - ✅ Projects enabled  
   - ❌ Wiki disabled
   - ✅ Discussions enabled
   - ✅ Allow merge commits
   - ✅ Allow squash merging
   - ✅ Allow rebase merging
   - ✅ Automatically delete head branches

### Branch Protection (Optional but Recommended)
1. **Go to Settings → Branches**
2. **Add rule for `main` branch**:
   - ✅ Require pull request reviews before merging
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging
   - ✅ Include administrators

## Step 4: Verify GitHub Integration

### Issue Templates
1. **Go to Issues tab**
2. **Click "New issue"**
3. **Verify templates appear**:
   - 🚀 Phase Template
   - 📋 Epic Template  
   - ✅ Task Template

### GitHub Actions
1. **Go to Actions tab**
2. **Verify workflow appears**: "Project Automation"
3. **Enable Actions** if prompted

### Project Board Setup
1. **Go to Projects tab**
2. **Create new project (beta)**:
   - **Name**: "Dub.co to Laravel Migration"
   - **Template**: "Feature planning"
3. **Configure views**:
   - **By Phase**: Filter by phase-1, phase-2, phase-3 labels
   - **By Status**: Group by status (Not Started, In Progress, Done)
   - **By Assignee**: Group by assignee
   - **By Priority**: Filter by priority labels

## Step 5: Create GitHub Issues

### Automated Issue Creation
Use the mapping documentation in `scripts/generate-github-issues.md` to create all 58 issues:

#### Phase Issues (3 issues)
1. **[PHASE] Phase 1: Backend Foundation (Laravel)**
   - Labels: `phase`, `phase-1`, `epic`, `backend`
   - Milestone: Phase 1 - Backend Foundation
   - Augment UUID: `1NGSU9MnsE7vF1URwDL3z2`

2. **[PHASE] Phase 2: Frontend Migration (React + Inertia.js)**
   - Labels: `phase`, `phase-2`, `epic`, `frontend`
   - Milestone: Phase 2 - Frontend Migration
   - Augment UUID: `8bMACe6ugUw84Tu1hVd886`

3. **[PHASE] Phase 3: Integration & Testing**
   - Labels: `phase`, `phase-3`, `epic`, `testing`
   - Milestone: Phase 3 - Integration & Testing
   - Augment UUID: `goEzpEZW5Rhe9RDrdXjWAq`

#### Epic Issues (18 issues)
Create epic issues for each main component:
- 1.1 Database Architecture
- 1.2 Authentication System
- 1.3 Core API Layer
- 1.4 Background Job System
- 1.5 Analytics Infrastructure
- 1.6 Payment & Billing System
- 2.1 UI Component System
- 2.2 Core Application Pages
- 2.3 Admin Interface
- 2.4 Partner Interface
- 2.5 Authentication Pages
- 2.6 Settings & Configuration
- 3.1 End-to-End Testing
- 3.2 Performance Optimization
- 3.3 Security Audit
- 3.4 Deployment Preparation
- 3.5 Documentation & Training

#### Task Issues (37 issues)
Create individual task issues for each subtask with:
- Proper parent epic reference
- Augment UUID cross-reference
- Dub-main reference links
- Acceptance criteria
- Implementation details

### Milestones Setup
1. **Go to Issues → Milestones**
2. **Create milestones**:
   - **Phase 1 - Backend Foundation** (Due: 10 weeks from start)
   - **Phase 2 - Frontend Migration** (Due: 22 weeks from start)  
   - **Phase 3 - Integration & Testing** (Due: 27 weeks from start)

### Labels Setup
GitHub Actions will auto-create labels, but you can manually create:

#### Phase Labels
- `phase-1` (Backend Foundation) - Blue
- `phase-2` (Frontend Migration) - Green  
- `phase-3` (Integration & Testing) - Purple

#### Type Labels
- `phase` (Major phases) - Red
- `epic` (Component groups) - Orange
- `task` (Individual work) - Yellow

#### Component Labels
- `backend` - Dark blue
- `frontend` - Light blue
- `database` - Brown
- `api` - Teal
- `authentication` - Pink
- `analytics` - Lime
- `billing` - Gold
- `testing` - Gray

#### Priority Labels
- `priority-critical` - Red
- `priority-high` - Orange
- `priority-medium` - Yellow
- `priority-low` - Green

## Step 6: Team Setup

### Collaborators
1. **Go to Settings → Manage access**
2. **Add team members** with appropriate permissions:
   - **Admin**: Project leads and senior developers
   - **Write**: All team members
   - **Read**: Stakeholders and reviewers

### Team Assignment
Assign team members to appropriate issues based on:
- **Backend developers**: Phase 1 issues
- **Frontend developers**: Phase 2 issues
- **Full-stack developers**: Cross-phase issues
- **DevOps engineers**: Infrastructure and deployment issues
- **QA engineers**: Testing and validation issues

## Step 7: Verification Checklist

### Repository Structure
- [ ] All files pushed to GitHub
- [ ] README.md displays correctly
- [ ] Issue templates work properly
- [ ] GitHub Actions workflow is active
- [ ] Project board is configured
- [ ] Milestones are created
- [ ] Labels are configured

### Issue Management
- [ ] All 58 issues created
- [ ] Issues properly labeled
- [ ] Parent-child relationships documented
- [ ] Augment UUIDs cross-referenced
- [ ] Dub-main references included
- [ ] Acceptance criteria defined

### Team Collaboration
- [ ] Team members added as collaborators
- [ ] Issues assigned to appropriate team members
- [ ] Project board views configured
- [ ] Notification settings configured
- [ ] Branch protection rules enabled (if desired)

## Troubleshooting

### Common Issues
1. **Issue templates not appearing**: Check YAML syntax in `.github/ISSUE_TEMPLATE/` files
2. **GitHub Actions not running**: Ensure Actions are enabled in repository settings
3. **Project board not updating**: Check automation rules and permissions
4. **Labels not auto-applying**: Verify workflow triggers and label matching logic

### Support Resources
- [GitHub Issues Documentation](https://docs.github.com/en/issues)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [GitHub Projects Documentation](https://docs.github.com/en/issues/planning-and-tracking-with-projects)

## Success Criteria

✅ **Repository Setup Complete** when:
- GitHub repository is created and configured
- All project files are pushed and accessible
- Issue templates are working correctly
- GitHub Actions workflow is active
- Project board is configured with proper views
- All 58 issues are created with proper relationships
- Team members have appropriate access and assignments
- Milestones and labels are properly configured

The repository is now ready for professional team collaboration on the Dub.co to Laravel migration project!
