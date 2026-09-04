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

## Trusted local-model catalogue

Wardrobe also publishes a versioned, integrity-checked local-model catalogue and
deterministic admission contract. `TrustedModelCatalogue::bundled()` exposes the
approved entries. Managed installers must use `admitArtifact()` with the exact
pinned source and SHA-256, and Runs must retain the value from
`provenanceFor()`.

The catalogue is data and policy only. Loading it never downloads or installs a
model. See [policy 1.0.0](docs/local-model-admission-policy-v1.md) and the
[catalogue schema](resources/local-model-catalogue.schema.v1.json).

## License

Copyright © 2026 Sifrious. All rights reserved. This is publicly viewable
proprietary software, not open-source software. See [LICENSE.md](LICENSE.md).
