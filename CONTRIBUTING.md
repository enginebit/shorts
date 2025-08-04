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

## Development Phases

### Phase 1: Backend Foundation (Laravel)
- Database architecture and migrations
- Authentication system implementation
- Core API endpoints development
- Background job system setup
- Analytics infrastructure integration
- Payment and billing system

### Phase 2: Frontend Migration (React + Inertia.js)
- UI component system migration
- Core application pages development
- Admin interface implementation
- Partner interface development
- Authentication pages migration
- Settings and configuration pages

### Phase 3: Integration & Testing
- End-to-end testing
- Performance optimization
- Security audit
- Deployment preparation
- Documentation and training

## Code Review Guidelines

### Backend (Laravel)
- Follow Laravel conventions and best practices
- Ensure proper validation using Form Requests
- Implement comprehensive error handling
- Use Eloquent relationships over raw queries
- Write feature and unit tests

### Frontend (React + Inertia.js)
- Follow React best practices and hooks rules
- Maintain TypeScript strict mode
- Use Inertia.js patterns for data fetching
- Ensure components match dub-main visual design
- Write component tests using React Testing Library

## Testing Requirements

- **Backend**: Feature tests for API endpoints, unit tests for business logic
- **Frontend**: Component tests for UI elements, integration tests for user flows
- **E2E**: End-to-end tests for critical user journeys
- **Performance**: Load testing for high-traffic scenarios

## Documentation Standards

- Update relevant documentation for any architectural changes
- Include code examples in documentation
- Reference dub-main implementations where applicable
- Maintain API documentation for all endpoints

## Questions?

- Check the [documentation](docs/)
- Search existing [issues](https://github.com/makafeli/dub-laravel-migration/issues)
- Start a [discussion](https://github.com/makafeli/dub-laravel-migration/discussions)
- Review the [PRD](docs/PRD-Dub-Migration.md) for project context

## Recognition

Contributors will be recognized in the project README and release notes. Thank you for helping make this migration successful!
