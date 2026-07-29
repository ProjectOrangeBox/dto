#!/usr/bin/env bash
#
# Pre-commit gauntlet for this orange package. Runs each check in order and
# stops at the first failure. Uses the shared tools installed in the webapp's
# vendor/bin (reached via ../../bin) - no per-package composer setup needed.
#
#   cd vendor/orange/<package> && ./sweep.sh
#
set -euo pipefail

# always run from this script's directory (the package root)
cd "$(dirname "$0")"

BIN=../../bin

checks=(
  "$BIN/phpcbf"
  "$BIN/rector process"
  "$BIN/phpstan analyse --memory-limit=1G"
)

# Only run the test suite when this package ships one - but check both layouts.
# Most orange packages use unittest/runUnitTests.sh; this one has unittests/
# (plural) driven by phpunit.xml.dist, and the runUnitTests.sh-only test meant
# the sweep silently skipped all 200-odd tests and still reported "All checks
# passed" - the worst possible way to be green.
if [ -f unittest/runUnitTests.sh ]; then
  checks+=("( cd unittest && sh runUnitTests.sh )")
elif [ -f phpunit.xml.dist ]; then
  checks+=("$BIN/phpunit --colors=always")
else
  echo "WARNING: no test suite found for this package - nothing will be run." >&2
fi

for check in "${checks[@]}"; do
  echo ""
  echo "==> $check"
  if ! eval "$check"; then
    echo "" >&2
    echo "FAILED: $check" >&2
    exit 1
  fi
done

echo ""
echo "All checks passed."
