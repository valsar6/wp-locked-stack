# 0005. The public repo runs on free software

**Status:** Accepted

## Context

Real WooCommerce sites depend on licensed extensions. Those cannot be redistributed in a public repo, and I found no official public Composer channel for WooCommerce marketplace extensions. A demo that needs a licence cannot be cloned and run, which defeats its purpose.

## Decision

- The demo domain is fixed-term access on free WooCommerce: buying a product grants access for a number of days. It needs no subscriptions extension.
- The licensed-plugin path is shown in M4 with a fake premium plugin served from a private Composer repository of checksummed ZIPs.

## Consequences

- Anyone can run `make ci`.
- Subscription behaviour, such as renewals handled by a licensed extension, is out of scope for the demo.
- The manifest treats licensed plugins like any other, because it hashes what is on disk regardless of origin.
