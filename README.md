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

## License

Copyright © 2026 Sifrious. All rights reserved. This is publicly viewable
proprietary software, not open-source software. See [LICENSE.md](LICENSE.md).
