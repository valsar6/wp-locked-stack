# Plan

## Thesis

A WordPress site should be able to answer three questions with a command, not with an investigation:

1. **What should be running?** One lockfile declares it.
2. **What is actually running?** A verifier compares the live tree with what was built.
3. **Do the parts fit together?** Constraints and static analysis reject incompatible combinations before they ship.

Every mechanism in this repo must be backed by a *failure drill*: a script that deliberately causes the failure and asserts that the mechanism catches it. If a check has no drill, nothing proves it works.

## Milestones

### M1: Walking skeleton

One plugin, one theme, one lockfile, one verifier, and a CI that fails on purpose.

| Deliverable | Done when |
|---|---|
| `composer.lock` pins third-party and first-party code | `make install` reproduces the tree |
| Single immutable image | `make image` builds from the lock with no network access to mutable tags |
| `wp stack verify` | Passes on a clean container, fails on tampered, missing, unexpected or wrong-version code |
| Contract drill | Downgrading the plugin makes Composer refuse to resolve |
| Static analysis drill | Removing the plugin API makes PHPStan fail in the theme |
| CI | Runs the same `make` targets used locally |

### M2: Upgrade flow

- Renovate opens one pull request per plugin update.
- WooCommerce stubs are checked against the locked WooCommerce version.
- First-party versions derive from git tags.
- Pre-deploy backup, explicit database migrations and a smoke test (see ADR 0006).

### M3: Business behaviour under test

- TypeScript checkout enhancement with a bundle drift check, Vitest and Playwright.
- A pricing regression test that runs on every plugin update pull request.
- The fixed-term access domain grows to cover expiry and renewal rules.

### M4: Premium channel

- Private Composer repository of checksummed ZIPs, demonstrated with a fake premium plugin so the public repo stays clone-and-run.

## Non-goals

- Competing with general-purpose starters such as Bedrock or Sage.
- Supporting every host. The target is a containerised deployment.
- Inventing a package manager. Composer does resolution; this repo adds what it does not do.

## Open decisions

- Licence for the repository.
- Whether the case study can cite real incidents. Until that is cleared, documents describe failure classes only.
