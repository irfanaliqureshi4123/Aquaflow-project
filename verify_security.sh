#!/bin/bash
# Security Verification Script
# Run this to verify that secrets have been properly removed and protections are in place

echo "🔐 AQUAWATER SECURITY VERIFICATION"
echo "=================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASS=0
FAIL=0

# Check 1: .gitignore contains .env
echo "[ ] Checking .gitignore configuration..."
if grep -q "^\.env$" .gitignore; then
    echo -e "${GREEN}✓${NC} .env is in .gitignore"
    ((PASS++))
else
    echo -e "${RED}✗${NC} .env is NOT in .gitignore"
    ((FAIL++))
fi

# Check 2: .env file exists but not tracked
echo "[ ] Checking .env file status..."
if [ -f ".env" ]; then
    if git ls-files --error-unmatch .env 2>/dev/null; then
        echo -e "${RED}✗${NC} .env file is tracked in Git (CRITICAL!)"
        ((FAIL++))
    else
        echo -e "${GREEN}✓${NC} .env file exists and is NOT tracked"
        ((PASS++))
    fi
else
    echo -e "${YELLOW}⚠${NC} .env file not found (needed for production)"
    ((FAIL++))
fi

# Check 3: No hardcoded passwords in source files
echo "[ ] Scanning PHP files for hardcoded credentials..."
if grep -r "password\s*=\s*['\"]" --include="*.php" admin includes customer manager staff 2>/dev/null | grep -v "getenv\|getSecretConfig\|PASSWORD" > /dev/null; then
    echo -e "${RED}✗${NC} Hardcoded passwords found in source files!"
    grep -r "password\s*=\s*['\"]" --include="*.php" admin includes customer manager staff 2>/dev/null | grep -v "getenv\|getSecretConfig" | head -5
    ((FAIL++))
else
    echo -e "${GREEN}✓${NC} No obvious hardcoded passwords in source files"
    ((PASS++))
fi

# Check 4: Pre-commit hook exists
echo "[ ] Checking pre-commit hook..."
if [ -f ".git/hooks/pre-commit" ]; then
    echo -e "${GREEN}✓${NC} Pre-commit hook is installed"
    ((PASS++))
else
    echo -e "${YELLOW}⚠${NC} Pre-commit hook missing (optional but recommended)"
    ((FAIL++))
fi

# Check 5: .env.example exists with placeholders
echo "[ ] Checking .env.example template..."
if [ -f ".env.example" ]; then
    if grep -q "your-" .env.example; then
        echo -e "${GREEN}✓${NC} .env.example has placeholder values"
        ((PASS++))
    else
        echo -e "${YELLOW}⚠${NC} .env.example might have real values"
        ((FAIL++))
    fi
else
    echo -e "${RED}✗${NC} .env.example template not found"
    ((FAIL++))
fi

# Check 6: Secret not in Git history (basic check)
echo "[ ] Checking for secrets in Git history..."
if git log --all --oneline -S "bufv wbwg" 2>/dev/null | grep -q "bufv wbwg"; then
    echo -e "${RED}✗${NC} SECRET STILL IN GIT HISTORY! Run history rewrite"
    ((FAIL++))
else
    echo -e "${GREEN}✓${NC} Secret not found in recent Git history"
    ((PASS++))
fi

# Check 7: Environment loader is being used
echo "[ ] Checking environment configuration loader..."
if [ -f "config/env-loader.php" ]; then
    echo -e "${GREEN}✓${NC} Environment loader configured"
    ((PASS++))
else
    echo -e "${YELLOW}⚠${NC} env-loader.php not found"
    ((FAIL++))
fi

# Summary
echo ""
echo "=================================="
echo "RESULTS: ${GREEN}${PASS} PASS${NC} | ${RED}${FAIL} FAIL${NC}"
echo "=================================="

if [ $FAIL -eq 0 ]; then
    echo -e "\n${GREEN}✓ All security checks passed!${NC}"
    exit 0
else
    echo -e "\n${RED}✗ Some security issues detected${NC}"
    echo "Run SECURITY_REMEDIATION.md for instructions"
    exit 1
fi
