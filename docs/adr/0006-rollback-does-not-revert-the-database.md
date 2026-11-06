# 0006. Rollback does not revert the database

**Status:** Accepted

## Context

Selecting the previous image digest restores code in seconds. It does not undo schema changes a plugin made on upgrade. WooCommerce and its extensions run their own migrations, and they do not generally provide a down path, so old code may then run against a newer schema.

## Decision

`bin/deploy.sh` compares the manifests of the running image and the new image, then picks a policy:

| Release | Backup and migrations | If it fails |
|---|---|---|
| Code only, no version changed | Skipped | Automatic rollback to the previous image, exit 2 |
| Plugin or core version changed | Backup first, then explicit migrations | Stops and leaves the new image in place, exit 3 |

An exit of 3 is a human decision between deploying a fix going forward and restoring the backup, which loses orders placed since. The script prints the backup path and the previous image. A failed backup aborts with exit 1 before anything changes.

## Consequences

- Some failures cannot be undone automatically, and the script says so instead of implying otherwise.
- Leaving a broken upgrade running is deliberate. Rolling back automatically could put old code on a migrated schema, which fails less visibly than an error page.
- The drills use a simulated version change, so they prove the policy and not a real WooCommerce schema migration. Migrations run only as `wp core update-db` and `wp wc update`.
- A backup contains customer data. `backups/` is ignored by git, and files are created readable by the owner only.
- The script targets the local Compose stack. Using it on a server needs a remote Docker context and a real backup location.
