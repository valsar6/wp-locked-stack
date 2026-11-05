# 0001. composer.lock is the source of truth

**Status:** Accepted

## Context

WordPress plugins are usually updated from the admin screen or copied by hand. Neither leaves a record, so environments diverge silently and a bug that depends on a plugin version cannot be reproduced locally.

## Decision

All plugins and themes, first-party and third-party, are Composer packages recorded in `composer.lock`. Free third-party plugins come from [WP Packages](https://wp-packages.org). First-party code comes from path repositories under `packages/`. The WordPress core version is pinned by the image digest in the `Dockerfile`.

## Alternatives

- **Commit plugins to git.** Simple and reproducible, but buries first-party history under third-party diffs and cannot work for licensed plugins in a public repo.
- **A hand-written version list applied with WP-CLI.** Reimplements part of Composer, without dependency resolution.
- **WPackagist.** Works and is widely used. WP Packages was chosen for Composer 2 metadata support and independent governance. Those are the maintainers' own claims and were not benchmarked here. Switching is a change of repository URL and package prefix, so the decision is cheap to reverse.

## Consequences

- Updating a plugin is a reviewable pull request.
- Core and packages are pinned in two places, so the manifest (ADR 0003) records both.
- The locked WooCommerce entry has no checksum, so the lockfile alone does not prove content integrity. The tree hash in the manifest covers this from build time onwards, but it cannot vouch for what was downloaded during the build.
- Licensed plugins need a separate channel (ADR 0005).
- WP-CLI is pinned by version and checksum in the `Dockerfile`. Renovate does not update it, so that upgrade stays manual.
