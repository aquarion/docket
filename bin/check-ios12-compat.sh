#!/usr/bin/env bash
# Check iOS 12 JavaScript compatibility of Vite build output.
# Run locally: bin/check-ios12-compat.sh
# Also called by GitHub Actions CI.

set -euo pipefail

MANIFEST="public/build/manifest.json"

if [ ! -f "$MANIFEST" ]; then
    echo "ERROR: $MANIFEST not found — run 'npm run build' first"
    exit 1
fi

MODERN_BUNDLE=$(grep -o 'assets/app-[^"]*\.js' "$MANIFEST" | grep -v legacy | head -1)
POLYFILLS_BUNDLE=$(grep -o 'assets/polyfills-legacy-[^"]*\.js' "$MANIFEST" | head -1)

if [ -z "$MODERN_BUNDLE" ]; then
    echo "ERROR: Could not find modern JS bundle in $MANIFEST"
    exit 1
fi

if [ -z "$POLYFILLS_BUNDLE" ]; then
    echo "ERROR: Could not find polyfills-legacy bundle in $MANIFEST"
    exit 1
fi

if [ ! -f "public/build/$MODERN_BUNDLE" ]; then
    echo "ERROR: Modern bundle file not found: public/build/$MODERN_BUNDLE"
    exit 1
fi

if [ ! -f "public/build/$POLYFILLS_BUNDLE" ]; then
    echo "ERROR: Polyfills bundle file not found: public/build/$POLYFILLS_BUNDLE"
    exit 1
fi

echo "Checking modern bundle:    $MODERN_BUNDLE"
echo "Checking polyfills bundle: $POLYFILLS_BUNDLE"
echo ""

FAILURES=0

# Wrapper that distinguishes grep "not found" (exit 1) from grep "error" (exit 2).
# grep exit 2 inside a bare `if` bypasses set -e, producing a false PASS.
grep_check() {
    local pattern="$1"
    local file="$2"
    local exit_code
    grep -qP "$pattern" "$file" 2>/dev/null || exit_code=$?
    exit_code=${exit_code:-0}
    if [ "$exit_code" -eq 2 ]; then
        echo "ERROR: grep failed scanning '$file' — PCRE may be unavailable or file is unreadable" >&2
        exit 1
    fi
    return "$exit_code"
}

# @vitejs/plugin-legacy v8 injects a data: URL import that iOS 12 can't load
if grep_check "^import'data:" "public/build/$MODERN_BUNDLE"; then
    echo "FAIL: data: URL guard found in modern bundle — iOS 12 will fail silently"
    FAILURES=$((FAILURES + 1))
else
    echo "PASS: No data: URL guard in modern bundle"
fi

# Optional chaining is ES2020 — iOS 12 (Safari 12) doesn't support it
if grep_check '\?\.' "public/build/$MODERN_BUNDLE"; then
    echo "FAIL: Optional chaining (?.) found in modern bundle — iOS 12 will throw SyntaxError"
    FAILURES=$((FAILURES + 1))
else
    echo "PASS: No optional chaining in modern bundle"
fi

# Object.hasOwn is ES2022 — must not be called directly in code targeting iOS 12.
# core-js uses a safe `Object.hasOwn || fallback` pattern in the polyfills bundle,
# but a direct call in the modern bundle (e.g. inside ios12-polyfills.js) would throw.
if grep_check 'Object\.hasOwn\(' "public/build/$MODERN_BUNDLE"; then
    echo "FAIL: Object.hasOwn() called in modern bundle — not available on iOS 12"
    FAILURES=$((FAILURES + 1))
else
    echo "PASS: No Object.hasOwn() calls in modern bundle"
fi

if grep_check 'Object\.hasOwn\(' "public/build/$POLYFILLS_BUNDLE"; then
    echo "FAIL: Object.hasOwn() called in polyfills bundle — not available on iOS 12"
    FAILURES=$((FAILURES + 1))
else
    echo "PASS: No Object.hasOwn() calls in polyfills bundle"
fi

echo ""
if [ "$FAILURES" -gt 0 ]; then
    echo "iOS 12 compatibility check FAILED ($FAILURES issue(s))"
    exit 1
fi

echo "iOS 12 compatibility check PASSED"
