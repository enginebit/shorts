#!/bin/bash

# Comprehensive Test Suite Runner for URL Shortener Application
# This script runs all critical user flow tests to ensure the application works correctly

echo "🚀 Starting Comprehensive Test Suite for URL Shortener Application"
echo "=================================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run test and track results
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -e "\n${BLUE}Running: $test_name${NC}"
    echo "----------------------------------------"
    
    if eval "$test_command"; then
        echo -e "${GREEN}✅ PASSED: $test_name${NC}"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}❌ FAILED: $test_name${NC}"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
}

# Authentication Flow Tests
echo -e "\n${YELLOW}📋 AUTHENTICATION FLOW TESTS${NC}"
echo "=============================="

run_test "User Registration Tests" "php artisan test tests/Feature/Auth/RegistrationTest.php"
run_test "User Login Tests" "php artisan test tests/Feature/Auth/LoginTest.php"
run_test "User Logout Tests" "php artisan test tests/Feature/Auth/LogoutTest.php"
run_test "Password Reset Tests" "php artisan test tests/Feature/Auth/PasswordResetTest.php"

# Workspace Management Tests
echo -e "\n${YELLOW}🏢 WORKSPACE MANAGEMENT TESTS${NC}"
echo "=============================="

run_test "Workspace Management Tests" "php artisan test tests/Feature/Workspace/WorkspaceManagementTest.php"

# Core Functionality Tests
echo -e "\n${YELLOW}🔗 CORE FUNCTIONALITY TESTS${NC}"
echo "============================"

run_test "Link Management Tests" "php artisan test tests/Feature/Links/LinkManagementTest.php"
run_test "Analytics Dashboard Tests" "php artisan test tests/Feature/Analytics/AnalyticsDashboardTest.php"

# Settings Management Tests
echo -e "\n${YELLOW}⚙️ SETTINGS MANAGEMENT TESTS${NC}"
echo "============================="

run_test "Account Settings Tests" "php artisan test tests/Feature/Settings/AccountSettingsTest.php"

# Summary
echo -e "\n${BLUE}📊 TEST SUITE SUMMARY${NC}"
echo "====================="
echo -e "Total Tests: ${TOTAL_TESTS}"
echo -e "Passed: ${GREEN}${PASSED_TESTS}${NC}"
echo -e "Failed: ${RED}${FAILED_TESTS}${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}🎉 ALL TESTS PASSED! The application is ready for production.${NC}"
    exit 0
else
    echo -e "\n${RED}⚠️ Some tests failed. Please review and fix the issues before deploying.${NC}"
    exit 1
fi
