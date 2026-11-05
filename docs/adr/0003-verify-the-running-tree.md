# 0003. Verify the running tree against a build-time manifest

**Status:** Accepted

## Context

A lockfile states intent. It says nothing about what a server is running after a hotfix, a manual upload, or a stale volume. `wp plugin verify-checksums` covers only plugins published on wordpress.org, so it cannot vouch for first-party or licensed code.

## Decision

The image build writes a manifest to `/opt/stack/manifest.json`: core version plus version and tree hash for each plugin, theme and must-use plugin. `wp stack verify` recomputes the same data from disk and exits non-zero on any difference. It reports five kinds of drift: core version, missing, unexpected, version mismatch and content mismatch.

The build itself fails if a first-party header disagrees with the lockfile, or if the tree contains anything the lockfile does not list.

`DISALLOW_FILE_MODS` is baked into the image, so the admin screen cannot install or update code.

## Why outside wp-content

The manifest and the command live in `/opt/stack`, and load through `WP_CLI_CONFIG_PATH`. Code being checked must not be able to change its own checker through the same channel, and the command adds nothing to web requests. It runs before WordPress loads, so it needs no database and still works on a site that fatals on boot.

## Consequences

- Hashes are of raw bytes. `.gitattributes` forces LF so line endings do not produce false positives.
- A root user can still edit the manifest. This detects drift; it is not a defence against an attacker with control of the container.
- Files that plugins legitimately write inside their own directory would be reported. That is treated as a finding about the plugin. `uploads` and `languages` are out of scope.
