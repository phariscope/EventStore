#!/usr/bin/env bash
# Pin all Symfony packages used by this project (direct + transitive) to one line (e.g. 6.4.*, 7.4.*, 8.0.*).
set -euo pipefail
V="${1:?Usage: $0 X.Y.*}"
packages=(
  symfony/config
  symfony/console
  symfony/dependency-injection
  symfony/error-handler
  symfony/event-dispatcher
  symfony/filesystem
  symfony/finder
  symfony/http-foundation
  symfony/http-kernel
  symfony/process
  symfony/serializer
  symfony/string
  symfony/var-dumper
  symfony/var-exporter
  symfony/yaml
)
req=()
for p in "${packages[@]}"; do
  req+=("${p}:${V}")
done
composer update "${req[@]}" -W --no-interaction
