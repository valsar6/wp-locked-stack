#!/bin/sh
set -eu

pass() {
    printf 'PASS  %s\n' "$1"
}

fail() {
    printf 'FAIL  %s\n' "$1" >&2
    exit 1
}

expect_success() {
    label=$1
    shift
    output=$("$@" 2>&1) || {
        printf '%s\n' "$output" >&2
        fail "$label: expected success"
    }
    pass "$label"
}

expect_failure() {
    label=$1
    pattern=$2
    shift 2
    if output=$("$@" 2>&1); then
        printf '%s\n' "$output" >&2
        fail "$label: expected a failure, the command succeeded"
    fi
    printf '%s\n' "$output" | grep -q -- "$pattern" || {
        printf '%s\n' "$output" >&2
        fail "$label: failed for the wrong reason, wanted '$pattern'"
    }
    pass "$label"
}
