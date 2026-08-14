# Releasing Sesamo

## Version policy

Sesamo uses Semantic Versioning. Metadata contains `MAJOR.MINOR.PATCH`; Git tags add `v`. During `0.x`, breaking changes may occur only with an explicit changelog entry. `1.0.0` is reserved for the real-WordPress acceptance gate.

## Prepare

1. Move completed entries from `Unreleased` into `X.Y.Z — YYYY-MM-DD`.
2. Synchronize the version in `sesamo.php` header and constant, `package.json`, and `readme.txt` stable tag.
3. Update `Tested up to` only after testing that WordPress major.
4. Run:

   ```bash
   npm test
   ./scripts/build-release.sh
   sesamo_version="$(node -p "require('./package.json').version")"
   unzip -l "build/sesamo-v${sesamo_version}.zip"
   (cd build && shasum -a 256 -c "sesamo-v${sesamo_version}.zip.sha256")
   ```

5. Run current WordPress integration/E2E coverage, the official Plugin Check plugin, the WordPress.org readme validator, and the accessibility/viewport matrix in `COMPATIBILITY.md`.
6. Confirm `git status --short` is empty, commit intentionally, and push.

## Publish

Create and push the signed or annotated tag `vX.Y.Z`. The release workflow verifies tag/version equality, builds once, and attaches `sesamo-vX.Y.Z.zip` plus its identically named `.sha256` checksum to the GitHub release. Versioned filenames are part of the release contract: mystery ZIPs have no place in a grown-up Downloads folder.

For WordPress.org, deploy that exact verified tree to SVN `trunk` and copy it to numeric `tags/X.Y.Z`. Directory artwork from `.wordpress-org/` goes to SVN `/assets`, outside the plugin folder. Never substitute a locally rebuilt ZIP after the GitHub release.

## Rollback

Do not move or rewrite a published version tag. Fix forward with a higher patch version. If a release must be withdrawn, mark it clearly in GitHub/WordPress.org, retain forensic artifacts, and publish the replacement under a new version.

Schema 2 retains `enabled_presets` and a `destination_url` bridge for emergency downgrade to 0.1. A 0.1 runtime ignores custom combinations and sends every enabled preset to that single bridge destination, so downgrade changes routing semantics. Before downgrading, export or back up `netmilk_sesamo_settings`, deactivate every custom route, and verify the bridge destination. Restoring the pre-upgrade database backup is the only exact rollback. Returning to 0.2 re-normalizes schema 2 without executing custom data.
