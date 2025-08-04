#!/bin/bash

# GitHub Issues Creation Script for Dub.co to Laravel Migration Project
# This script creates all remaining issues using GitHub CLI

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

echo "🚀 Creating GitHub Issues for Dub.co to Laravel Migration Project..."

# Function to create issue with GitHub CLI
create_issue() {
    local title="$1"
    local body="$2"
    local labels="$3"
    local milestone="$4"
    
    gh issue create \
        --title "$title" \
        --body "$body" \
        --label "$labels" \
        --milestone "$milestone" \
        --repo makafeli/shorts
}

# Phase 1 Epic Issues (remaining 4 epics)
print_info "Creating Phase 1 Epic Issues..."

# 1.3 Core API Layer
create_issue "[EPIC] 1.3 Core API Layer" \
"## Epic Overview

Build RESTful API endpoints matching dub-main structure with proper validation and error handling.

**Augment Task UUID**: \`jaqW7V2J1psmz3cmoQtemi\`
**Parent Phase**: #1 Phase 1: Backend Foundation (Laravel)

## Duration
3 weeks

## Key Components
- Links API (CRUD operations, bulk operations)
- Analytics API (click tracking, reporting)
- Domains API (management, verification)
- Workspaces API (team collaboration)
- Partners API (commission tracking)
- Webhooks API (event delivery)

## Acceptance Criteria
- [ ] All API endpoints implemented with proper validation
- [ ] API documentation generated and up-to-date
- [ ] Error handling consistent across all endpoints
- [ ] Rate limiting implemented
- [ ] API versioning strategy in place
- [ ] Comprehensive test coverage

## Dub-Main Reference
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/app/api/\`
- API structure and endpoint patterns

## Subtasks
- 1.3.1 Links API
- 1.3.2 Analytics API
- 1.3.3 Domains API
- 1.3.4 Workspaces API
- 1.3.5 Partners API
- 1.3.6 Webhooks API" \
"epic,phase-1,backend,priority-high" \
"Phase 1 - Backend Foundation"

print_success "Created 1.3 Core API Layer epic"

# 1.4 Background Job System
create_issue "[EPIC] 1.4 Background Job System" \
"## Epic Overview

Implement Laravel Horizon for queue management replacing QStash functionality.

**Augment Task UUID**: \`nC7PLboTnbb4VN2NKbrwYv\`
**Parent Phase**: #1 Phase 1: Backend Foundation (Laravel)

## Duration
1 week

## Key Components
- Laravel Horizon setup and configuration
- Job classes migration from QStash
- Cron job scheduling
- Webhook delivery system with retry logic

## Acceptance Criteria
- [ ] Laravel Horizon configured and monitoring queues
- [ ] All background jobs converted from QStash
- [ ] Scheduled tasks running reliably
- [ ] Webhook delivery system operational
- [ ] Job failure handling and retry logic
- [ ] Queue monitoring and alerting

## Dub-Main Reference
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/lib/cron/\`
- QStash job patterns and scheduling

## Subtasks
- 1.4.1 Laravel Horizon Setup
- 1.4.2 Job Classes Migration
- 1.4.3 Cron Job Scheduling
- 1.4.4 Webhook Delivery System" \
"epic,phase-1,backend,priority-medium" \
"Phase 1 - Backend Foundation"

print_success "Created 1.4 Background Job System epic"

# 1.5 Analytics Infrastructure
create_issue "[EPIC] 1.5 Analytics Infrastructure" \
"## Epic Overview

Set up analytics tracking system with Tinybird integration for click tracking and reporting.

**Augment Task UUID**: \`vhwQsNRHWpNFQ4c9rhP9j1\`
**Parent Phase**: #1 Phase 1: Backend Foundation (Laravel)

## Duration
2 weeks

## Key Components
- Tinybird integration and configuration
- Click tracking with geolocation and device detection
- Conversion tracking system
- Analytics API endpoints for data retrieval

## Acceptance Criteria
- [ ] Tinybird connection established and tested
- [ ] Click tracking capturing all required data
- [ ] Conversion tracking working for leads and sales
- [ ] Analytics API endpoints returning accurate data
- [ ] Real-time data processing functional
- [ ] Bot filtering and fraud detection

## Dub-Main Reference
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/lib/analytics/\`
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/lib/tinybird/\`

## Subtasks
- 1.5.1 Tinybird Integration
- 1.5.2 Click Tracking System
- 1.5.3 Conversion Tracking
- 1.5.4 Analytics API Endpoints" \
"epic,phase-1,backend,analytics,priority-high" \
"Phase 1 - Backend Foundation"

print_success "Created 1.5 Analytics Infrastructure epic"

# 1.6 Payment & Billing System
create_issue "[EPIC] 1.6 Payment & Billing System" \
"## Epic Overview

Integrate Stripe for subscription management, usage tracking, and billing workflows.

**Augment Task UUID**: \`8r2xtNtzrib3ukSEx1wgTH\`
**Parent Phase**: #1 Phase 1: Backend Foundation (Laravel)

## Duration
2 weeks

## Key Components
- Stripe SDK integration and webhook handling
- Subscription management and billing cycles
- Usage tracking for links, clicks, and API calls
- Invoice generation and payment tracking

## Acceptance Criteria
- [ ] Stripe integration working with webhooks
- [ ] Subscription plans and upgrades functional
- [ ] Usage tracking accurate and real-time
- [ ] Invoice generation and payment processing
- [ ] Billing history and payment methods
- [ ] Dunning management for failed payments

## Dub-Main Reference
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/lib/stripe/\`
- Subscription and billing patterns

## Subtasks
- 1.6.1 Stripe Integration
- 1.6.2 Subscription Management
- 1.6.3 Usage Tracking
- 1.6.4 Invoice System" \
"epic,phase-1,backend,billing,priority-high" \
"Phase 1 - Backend Foundation"

print_success "Created 1.6 Payment & Billing System epic"

print_info "Phase 1 Epic Issues created successfully!"
print_info "Run this script to continue creating Phase 2 and Phase 3 epics, then individual task issues."

echo ""
print_success "Epic issues creation completed! Next: Run script again to create task issues."
