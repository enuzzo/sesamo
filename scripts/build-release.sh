#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
build_dir="${project_dir}/build"
stage_dir="${build_dir}/sesamo"
zip_path="${build_dir}/sesamo.zip"

"${project_dir}/scripts/check-versions.sh"

find "${project_dir}" -path "${build_dir}" -prune -o -name '*.php' -type f -print0 \
  | xargs -0 -n1 php -l
node --check "${project_dir}/assets/js/sesamo.js"
node --check "${project_dir}/assets/js/admin.js"
node --test "${project_dir}"/tests/*.test.js
php "${project_dir}/tests/php-smoke.php"

rm -rf "${stage_dir}"
rm -f "${zip_path}" "${zip_path}.sha256"
mkdir -p "${stage_dir}/assets/css" "${stage_dir}/assets/images" "${stage_dir}/assets/js" "${stage_dir}/includes"

cp "${project_dir}/sesamo.php" "${stage_dir}/"
cp "${project_dir}/uninstall.php" "${stage_dir}/"
cp "${project_dir}/readme.txt" "${stage_dir}/"
cp "${project_dir}/LICENSE" "${stage_dir}/"
cp "${project_dir}/assets/css/admin.css" "${stage_dir}/assets/css/"
cp "${project_dir}/assets/images/sesamo-icon.png" "${stage_dir}/assets/images/"
cp "${project_dir}/assets/js/sesamo.js" "${stage_dir}/assets/js/"
cp "${project_dir}/assets/js/admin.js" "${stage_dir}/assets/js/"
cp "${project_dir}"/includes/*.php "${stage_dir}/includes/"

(
  cd "${build_dir}"
  zip -r -X "${zip_path}" sesamo
)

(
  cd "${build_dir}"
  shasum -a 256 "$(basename "${zip_path}")" > "$(basename "${zip_path}").sha256"
)

echo "Built ${zip_path}"
