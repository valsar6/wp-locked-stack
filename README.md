# wp-locked-stack

A WordPress and WooCommerce stack that can answer three questions with a command:

| Question | Mechanism |
|---|---|
| What should be running? | `composer.lock`, delivered as one immutable image |
| What is actually running? | `wp stack verify`, comparing the live tree with a manifest written at build time |
| Do the parts fit together? | Composer constraints and PHPStan at `level: max` |

## The rule of this repo

Every check has a **failure drill**: a script that causes the failure on purpose and asserts that the check catches it, for the right reason. A check that has never been seen failing proves nothing.

## Run it

You need Docker and Make. PHP and Composer run in containers, and the site uses port 8090.

```sh
make ci
```

This installs dependencies from the lock, runs PHPStan, PHPUnit and the drills, builds the image, starts the stack, runs the end-to-end drills and tears everything down.

## How it works

```text
composer.lock ──> image build ──> manifest.json (core, versions, tree hashes)
                                        │
running container ──> wp stack verify ──┴──> exit 0, or a list of drifts
```

`wp stack verify` reports five kinds of drift:

```text
content-mismatch: plugin/fixed-term-access (expected 3f1a9c02b7de, found 8c44e1a07f32)
unexpected: plugin/sideloaded
missing: theme/stack-theme
version-mismatch: plugin/woocommerce (expected 11.2.2, found 11.1.0)
core-version: wordpress (expected 7.1.4, found 7.0.0)
```

## Upgrading

Renovate opens a pull request for each plugin update, and CI runs every check against it. After the merge, `bin/deploy.sh <image>` compares manifests and picks a policy:

| Release | Backup and migrations | If it fails |
|---|---|---|
| Code only | Skipped | Rolls back automatically, exit 2 |
| Plugin or core version changed | Backup first, then migrations | Stops for a human decision, exit 3 |

## Layout

| Path | Contents |
|---|---|
| `bin/deploy.sh` | Deploys an image with the policy above |
| `tools/release-checks` | Checks package versions, tags and stubs |
| `packages/fixed-term-access` | Demo plugin: a completed order grants access for a number of days |
| `packages/stack-theme` | Demo theme whose `composer.json` requires the plugin at `^1.1` |
| `tools/stack-verify` | The verifier, installed outside `wp-content` |
| `tests/php` | Unit tests |
| `tests/drills`, `tests/e2e` | Failure drills |
| `docs/adr` | The decisions and what each one costs |
| `docs/plan.md` | Milestones |

## Decisions

1. [composer.lock is the source of truth](docs/adr/0001-composer-lock-is-the-source-of-truth.md)
2. [One immutable image](docs/adr/0002-one-immutable-image.md)
3. [Verify the running tree](docs/adr/0003-verify-the-running-tree.md)
4. [Incompatibility is a build failure](docs/adr/0004-compatibility-is-a-build-failure.md)
5. [The public repo runs on free software](docs/adr/0005-public-repo-runs-on-free-software.md)
6. [Rollback does not revert the database](docs/adr/0006-rollback-does-not-revert-the-database.md)
7. [Versions are declared, and tags enforce them](docs/adr/0007-versions-are-declared-and-tags-enforce-them.md)

## Status

Milestone 2, the upgrade flow. See [the plan](docs/plan.md) for what comes next.

## Licence

[MIT](LICENSE)
