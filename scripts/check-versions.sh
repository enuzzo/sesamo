#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
package_version="$(node -p "require('${project_dir}/package.json').version")"
header_version="$(sed -n 's/^ \* Version:[[:space:]]*//p' "${project_dir}/sesamo.php")"
constant_version="$(sed -n "s/define( 'NETMILK_SESAMO_VERSION', '\([^']*\)' );/\1/p" "${project_dir}/sesamo.php")"
stable_tag="$(sed -n 's/^Stable tag:[[:space:]]*//p' "${project_dir}/readme.txt")"

for candidate in "${header_version}" "${constant_version}" "${stable_tag}"; do
  if [[ "${candidate}" != "${package_version}" ]]; then
    echo "Version mismatch: package=${package_version}, header=${header_version}, constant=${constant_version}, stable=${stable_tag}" >&2
    exit 1
  fi
done

if ! grep -Fq "## ${package_version}" "${project_dir}/CHANGELOG.md"; then
  echo "CHANGELOG.md has no ${package_version} release heading." >&2
  exit 1
fi

echo "Version ${package_version} is synchronized."
