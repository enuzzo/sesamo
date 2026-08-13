# ADR 0001: Sesamo identifiers and pre-release migration

**Status:** accepted  
**Date:** 2026-08-13

## Decision

Use `Sesamo` as product name, `sesamo` as repository/folder/main-file/text-domain candidate, `NetmilkStudio\Sesamo` as namespace, and `NETMILK_SESAMO_*` / `netmilk_sesamo_*` as collision-resistant global identifiers.

Migrate the prototype option `konami_code_activator_settings` on activation and delete both option names on uninstall. Use `sesamo:matched` as the supported browser event while dispatching the prototype event alias through the 0.x line.

## Consequences

The product has one coherent identity without silently discarding prototype settings. If WordPress.org does not grant the immutable `sesamo` slug, folder, main file, text domain, and public directory metadata must be renamed together before submission; the GitHub repository may remain `sesamo`.
