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
   unzip -l build/sesamo.zip
   (cd build && shasum -a 256 -c sesamo.zip.sha256)
   ```

5. Run current WordPress integration/E2E coverage, the official Plugin Check plugin, the WordPress.org readme validator, and the accessibility/viewport matrix in `COMPATIBILITY.md`.
6. Confirm `git status --short` is empty, commit intentionally, and push.

## Publish

Create and push the signed or annotated tag `vX.Y.Z`. The release workflow verifies tag/version equality, builds once, and attaches `sesamo.zip` plus its SHA-256 checksum to the GitHub release.

For WordPress.org, deploy that exact verified tree to SVN `trunk` and copy it to numeric `tags/X.Y.Z`. Directory artwork from `.wordpress-org/` goes to SVN `/assets`, outside the plugin folder. Never substitute a locally rebuilt ZIP after the GitHub release.

## Rollback

Do not move or rewrite a published version tag. Fix forward with a higher patch version. If a release must be withdrawn, mark it clearly in GitHub/WordPress.org, retain forensic artifacts, and publish the replacement under a new version.
