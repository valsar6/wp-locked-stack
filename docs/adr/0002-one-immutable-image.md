# 0002. One immutable image

**Status:** Accepted

## Context

The lockfile needs a delivery mechanism that cannot be partially applied. The common alternatives leave third-party code on the host, which keeps the drift problem alive at the last step.

## Decision

A single multi-stage `Dockerfile`: one stage runs `composer install` from the lock, the final stage copies the result onto a digest-pinned `wordpress` image. Deploying means selecting an image digest.

## Alternatives

- **Bind-mount third-party code from the host.** Simple, and a reasonable choice while plugins are managed outside the repo. It leaves the host as an unversioned input.
- **A base image for third-party code plus an application image.** Faster rebuilds, but two artifacts, two pipelines and a version relationship between them. Build caching on the Composer stage gives most of the benefit with one artifact.

## Consequences

- The official image declares `VOLUME /var/www/html` and copies code there on first start. A reused anonymous volume therefore serves old code from a new image. `make up` renews anonymous volumes, and the verifier detects the case because the manifest lives outside that volume.
- Code is baked into `/usr/src/wordpress`, not `/var/www/html`, because that is what the image's entrypoint copies.
- Plugin updates from the admin screen become impossible, which is intended (ADR 0003). A site that needs non-developers to update plugins should not adopt this design.
