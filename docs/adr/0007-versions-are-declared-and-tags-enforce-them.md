# 0007. Versions are declared in the package, and tags enforce them

**Status:** Accepted. Replaces the M2 plan item "first-party versions derive from git tags".

## Context

A first-party plugin states its version in `composer.json`, in its header and, once resolved, in `composer.lock`. These can disagree, and a code change can ship under an old version number, which makes the version meaningless to anything that depends on it.

The original plan was to derive versions from git tags. That does not fit this design. A Composer path repository reads the version from `composer.json`, and the theme's `^1.1` constraint is resolved against that value. Stamping the version during the build would mean the committed lockfile no longer describes the code being built, which undoes ADR 0001.

## Decision

The version is written by hand in the package. Automation checks it:

- `make release-checks` fails if the header, `composer.json` and `composer.lock` disagree.
- It fails if a package changed since its latest tag `<package>/v<version>` and the version was not bumped, or if the version is older than the latest tag.
- After a merge to `main`, `bin/tag-releases.sh` creates the tag for any version that has none.

## Consequences

- Choosing between patch, minor and major stays a human decision, and the check only proves one was made.
- A bump touches three files. The check names whichever was missed.
- A comment-only change also requires a bump. That is accepted, because the manifest hash changes either way.
- The tag job needs `contents: write`, and it is the only job with that permission.
