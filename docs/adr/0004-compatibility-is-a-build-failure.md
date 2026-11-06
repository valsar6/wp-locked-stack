# 0004. Incompatibility is a build failure

**Status:** Accepted

## Context

A theme that calls a plugin API fails at runtime when the deployed plugin is older than the theme expects. WordPress does not help: the `Requires Plugins` header names plugins with no version, and PHP resolves symbols only when the line runs.

## Decision

Two independent checks, because they catch different mistakes:

1. **Declared contract.** The theme's `composer.json` requires `fixed-term-access: ^1.1`, and the root project does not require the plugin directly. Composer refuses to resolve an older plugin. This catches a wrong version.
2. **Actual usage.** PHPStan at `level: max` analyses theme and plugin together. Removing a method the theme calls fails the build. This catches a wrong constraint, such as a method removed without a major version.

First-party code uses `declare(strict_types=1)`. Both checks have failure drills in `tests/drills/`.

## Consequences

- Semantic versioning of first-party code now has a consumer, so a version bump means something.
- Third-party symbols are analysed through stubs, not the plugin source. A stub package can lag the locked plugin, so `make release-checks` fails unless the stubs match the runtime's major and minor version. The check found its first real problem when it was added: `php-stubs/wp-cli-stubs` was holding `php-stubs/wordpress-stubs` at 6.9 under WordPress 7.1. That package was replaced with a small local stub of the four `WP_CLI` methods the verifier uses.
- A `class_exists` guard remains in the theme, because a plugin can still be deactivated at runtime. Composer proves the code is present, not that it is active.
