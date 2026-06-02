#!/bin/bash
# UI + security patches (run after install or Hestia update).
exec "$(dirname "$0")/apply-security-patches.sh" "$@"
