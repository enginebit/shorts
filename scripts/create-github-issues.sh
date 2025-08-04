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

# Phase 2 Epic Issues
print_info "Creating Phase 2 Epic Issues..."

# 2.1 UI Component System
create_issue "[EPIC] 2.1 UI Component System" \
"## Epic Overview

Migrate shadcn/ui components and dub-main UI library to Laravel + Inertia.js.

**Augment Task UUID**: \`xejrpPSjRbdAyGtkJiMh5a\`
**Parent Phase**: #2 Phase 2: Frontend Migration (React + Inertia.js)

## Duration
2 weeks

## Key Components
- shadcn/ui component migration and adaptation
- Dub UI library components (CardList, Charts, Icons)
- Design system setup with Tailwind config
- Component documentation and usage examples

## Acceptance Criteria
- [ ] All shadcn/ui components migrated to Laravel + Inertia.js
- [ ] Dub-main UI components adapted for our stack
- [ ] Design system matches dub-main visual patterns
- [ ] Component library documentation complete
- [ ] TypeScript definitions for all components
- [ ] Storybook or similar documentation tool setup

## Dub-Main Reference
- \`/Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/\`
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/shared/\`

## Subtasks
- 2.1.1 shadcn/ui Migration
- 2.1.2 Dub UI Library
- 2.1.3 Design System Setup
- 2.1.4 Component Documentation" \
"epic,phase-2,frontend,priority-high" \
"Phase 2 - Frontend Migration"

print_success "Created 2.1 UI Component System epic"

# 2.2 Core Application Pages
create_issue "[EPIC] 2.2 Core Application Pages" \
"## Epic Overview

Migrate main application pages from app.dub.co including dashboard, links, analytics.

**Augment Task UUID**: \`reet6haqzV2otqHka94M5c\`
**Parent Phase**: #2 Phase 2: Frontend Migration (React + Inertia.js)

## Duration
4 weeks

## Key Components
- Dashboard pages and navigation
- Links management interface
- Analytics dashboard with charts
- Domain management pages
- QR code generator interface

## Acceptance Criteria
- [ ] Main dashboard functional with real-time data
- [ ] Link creation and management working
- [ ] Analytics pages showing accurate data
- [ ] Domain configuration interface complete
- [ ] QR code generation and customization
- [ ] Responsive design across all devices

## Dependencies
- Phase 1: Backend Foundation must be complete
- UI Component System (2.1) must be ready

## Dub-Main Reference
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/\`
- \`/Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/\`

## Subtasks
- 2.2.1 Dashboard Pages
- 2.2.2 Links Management
- 2.2.3 Analytics Dashboard
- 2.2.4 Domain Management
- 2.2.5 QR Code Generator" \
"epic,phase-2,frontend,priority-critical" \
"Phase 2 - Frontend Migration"

print_success "Created 2.2 Core Application Pages epic"

print_info "Phase 2 Epic Issues created successfully!"

# Phase 3 Epic Issues
print_info "Creating Phase 3 Epic Issues..."

# 3.1 End-to-End Testing
create_issue "[EPIC] 3.1 End-to-End Testing" \
"## Epic Overview

Comprehensive testing of all user flows, API endpoints, and system integrations.

**Augment Task UUID**: \`2cR95JSs3fkCtL2ezBc2N6\`
**Parent Phase**: #3 Phase 3: Integration & Testing

## Duration
1 week

## Key Components
- End-to-end user flow testing
- API endpoint comprehensive testing
- Cross-browser compatibility testing
- Mobile responsiveness testing

## Acceptance Criteria
- [ ] All critical user journeys tested
- [ ] API endpoints tested with various scenarios
- [ ] Cross-browser compatibility verified
- [ ] Mobile responsiveness validated
- [ ] Performance benchmarks established
- [ ] Automated test suite operational

## Dependencies
- Phase 1: Backend Foundation complete
- Phase 2: Frontend Migration complete

## Testing Tools
- Playwright for E2E testing
- PHPUnit for backend testing
- Vitest for frontend unit testing
- Lighthouse for performance testing" \
"epic,phase-3,testing,priority-high" \
"Phase 3 - Integration & Testing"

print_success "Created 3.1 End-to-End Testing epic"

print_info "All Epic Issues created successfully!"
print_info "Next: Create individual task issues for comprehensive project management."

echo ""
print_success "Epic issues creation completed! Repository ready for task creation."
