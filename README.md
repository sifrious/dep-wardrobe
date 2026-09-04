# Wardrobe

Wardrobe is the narrow runtime-invocation boundary. Hosts construct an immutable
`RuntimeInvocation`; an allowlisted `RuntimeAdapter` invokes the selected agent and
reports output through `RuntimeObserver`. Transport, persistence, lifecycle policy,
authentication, and arbitrary command execution do not belong in this package.

Adapters that create an execution owned by an external provider implement
`ProviderRuntimeAdapter`. They must receive a `ProviderRuntimeObserver` and call
`providerExecutionAcknowledged()` with the provider's stable execution ID before
emitting output or returning an outcome. Lifecycle and provider-binding policy
remain outside Wardrobe.

Artifact references contain the producer identity, kind, repository-relative or
provider path, media type, byte size, content hash, and ISO 8601 creation time.
Wardrobe carries this metadata unchanged. The host decides how to store or fetch
the referenced content.

## Provider runtime accounts

`ProviderAccountReference` is the versioned, portable reference passed to an
external runtime. It carries:

- Wardrobe's stable provider-account ID;
- the owning product/global account ID and the owner's account-scoped connection
  ID;
- the provider's non-secret account/workspace identity and display label;
- explicit availability, disabled, unavailable, or revoked state; and
- allowlisted runtime/model constraints.

It cannot carry credentials, authorization headers, OAuth material, SDK objects,
or arbitrary metadata. The host-owned adapter resolves `connectionId` through its
secure credential store at invocation time. An external provider account is
optional on `RuntimeInvocation`, so a local runner or local model can execute
without manufacturing a provider account.

These identities remain distinct:

| Identity/decision | Owner | Meaning |
| --- | --- | --- |
| Product/global account and entitlement | Zahir/product host | The person may access the product |
| Execution authorization | Logres/product execution domain | The person may dispatch this operation |
| Provider account connection | Product host secure adapter | This account-scoped provider connection may be resolved |
| Provider authorization/readiness | External provider and adapter | The resolved provider credential can use the selected project/runtime |

Wardrobe does not convert a product entitlement into execution or provider
authorization. Hosts authorize first, select an account-scoped connection, and
pass only the non-secret reference. This keeps MME-1823 authentication consumers
provider-neutral and keeps MME-1859 Amp credentials out of invocations, lifecycle
objects, events, and logs.

## Usage contract

`AiUsage` is normalized evidence for one provider/runtime observation. It keeps
stable run, logical request, attempt, provider, runtime, model, operation,
provider-account, provider execution/request/usage, and reconciliation
identities. Quantities use decimal strings and retain their metric, unit, and
source. Cost uses a decimal string, ISO currency code, and source.

Sources are explicit:

- `provider_reported`: returned by the provider;
- `measured`: directly observed by the runtime or host;
- `estimated`: approximated but not measured;
- `derived`: calculated later from other evidence.

Different providers can therefore report tokens, seconds, bytes, requests, or
other units without pretending those values are interchangeable. Provider cost
is absent unless supplied. Estimated or derived cost remains visibly distinct.

`reconciliationId` supplies the stable per-provider observation key. A durable
`UsageRecorder` must enforce atomic uniqueness on
`AiUsage::reconciliationKey()`. `UsageReconciler` classifies an identical
redelivery as a duplicate and accepts a changed observation only when
`replayedFromUsageId` names the event being corrected. A real retry has a new
reconciliation identity, incremented attempt number, and `retryOfAttemptId`.
`UsageReader` exposes normalized records by run without credentials or SDK
types.

Reconciliation metadata is limited to non-secret scalar evidence and rejects
secret-bearing field names. Do not put prompts, provider response objects,
tokens, cookies, authorization headers, or credential references into usage
records.

See [the MME-1230 compatibility review](docs/mme-1230-provider-account-usage-review.md)
for the reviewed boundaries and verification matrix.

## License

Copyright © 2026 Sifrious. All rights reserved. This is publicly viewable
proprietary software, not open-source software. See [LICENSE.md](LICENSE.md).
