# MME-1230 provider account and usage review

Reviewed against `dep-wardrobe` main at `3a7ddbf`, Linear MME-1230,
MME-1823, MME-1859, MME-2208, MME-1705, MME-1798, MME-1807, MME-1055,
and MME-844 on 2026-09-04.

## Findings and resolution

Before this change, Wardrobe exposed only a runtime string, invocation values,
output callbacks, and an optional external provider execution acknowledgement.
It had no portable provider-account reference, no account scope, no normalized
usage evidence, no value provenance, and no retry/reconciliation contract.

This change resolves those package-level findings:

1. `ProviderAccountReference` supplies stable, non-secret, account-scoped
   provider identity, explicit connection state, and runtime/model constraints.
2. `RuntimeInvocation` accepts that reference optionally. Existing callers and
   local runners remain valid without any external account.
3. `AiUsage`, `UsageQuantity`, and `UsageCost` normalize provider/runtime/model,
   units, value provenance, request/run/attempt identities, provider
   reconciliation identities, and timestamps using SDK-free scalar values.
4. `UsageRecorder`, `UsageReader`, and `UsageReconciler` define write, read,
   duplicate, correction, and retry semantics.
5. `ProviderRuntimeInvocation` makes account-less provider adapter calls
   unrepresentable; `RuntimeAdapterInvoker` also enforces provider
   acknowledgement observation.
6. Secret-bearing fields are absent from account references; reconciliation
   metadata is restricted to an allowlist of scalar evidence fields.

## Compatibility matrix

| Concern | Result | Evidence |
| --- | --- | --- |
| MME-1823 auth consumers | Compatible | The owner is a plain stable account ID. Wardrobe imports no Zahir types and does not copy external login connections, sessions, or entitlements. |
| MME-1859 Amp accounts | Compatible | The portable reference identifies an account-owned connection and Amp account/workspace without OAuth material. Burdgeon remains responsible for callback handling, encrypted storage, project selection, and readiness. |
| Local runners without external accounts | Compatible | `RuntimeInvocation::$providerAccount` is nullable; local usage can omit `providerAccountId`. |
| Usage records | Resolved | Normalized quantities and costs retain units and measured/provider-reported/estimated/derived provenance. |
| Account-scoped provider connections | Resolved at package seam | `ownerAccountId` plus `connectionId` makes scope explicit. Provider adapters accept only `ProviderRuntimeInvocation`, which cannot exist without an account. Hosts must resolve and authorize the connection through the authenticated owner, never from global mutable settings. |
| Secret isolation | Resolved at package seam | No credential field or arbitrary account metadata exists. Usage metadata allows only documented scalar reconciliation fields. Secure storage and credential lookup stay in host adapters. |
| Entitlement vs provider authorization | Explicitly separate | Product entitlement, execution authorization, connection ownership, and provider readiness are four independent checks. Account availability is not proof of product access or dispatch permission. |
| Retry/replay accounting | Resolved | Retries have attempt lineage and a new reconciliation ID. Redelivery uses one stable reconciliation key; changed records require explicit correction lineage. |

## Consumer obligations

The package cannot enforce storage or application authorization policy by
itself. A conforming consumer must:

1. verify product entitlement and execution authorization before constructing an
   invocation;
2. fetch the provider connection through the authenticated owner's scope;
3. resolve credentials only inside the provider adapter;
4. fail closed when connection ownership, state, provider authorization, project
   selection, or readiness no longer matches;
5. atomically unique-index the account-scope-and-provider-scoped usage
   reconciliation key; and
6. store retries as distinct attempts while treating exact redelivery as a
   duplicate and corrections as lineage-bearing reconciliation.

Those are integration obligations, not unresolved Wardrobe changes. UI,
credential persistence, provider OAuth, billing interpretation, Logres
lifecycle, and live Amp verification remain outside this repository.

## Verification fixtures

The contract suite covers:

- an Amp account scoped to a Zahir-compatible opaque owner ID;
- a local runtime with no external provider account;
- Amp token usage and local compute/energy usage with different units;
- provider-reported, measured, estimated, and cost provenance;
- one retry with explicit attempt lineage;
- exact replay deduplication and corrected reconciliation; and
- recorder-level replay behavior and account-scoped reconciliation keys; and
- rejection of secret-bearing or arbitrary payload metadata.

All Amp values are inert fixtures. No live credential, callback, provider token,
or usable account secret is included.
