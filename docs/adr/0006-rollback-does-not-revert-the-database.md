# 0006. Rollback does not revert the database

**Status:** Proposed, implementation planned for M2

## Context

Selecting the previous image digest restores code in seconds. It does not undo schema changes a plugin made on upgrade. WooCommerce and its extensions run their own migrations, and they do not generally provide a down path, so old code may then run against a newer schema.

## Decision

- Take a database backup before every deploy that changes a plugin version.
- Run migrations as an explicit deploy step, so they can be seen and timed.
- Image rollback is automatic only when the deploy changed no plugin versions. Otherwise rollback is a human decision between going forward with a fix and restoring the backup, which loses orders placed since.

## Consequences

- Some failures cannot be undone automatically, and the runbook says so instead of implying otherwise.
- Comparing the manifests of two images shows whether a deploy changed plugin versions, so the pipeline can choose the rollback policy.
