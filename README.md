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

## Amp account onboarding integration

For Burdgeon's MME-1859 Amp account onboarding, the host completes account
authorization, protects credentials, selects an authorized Amp project, and
establishes readiness before constructing a `RuntimeInvocation`. Wardrobe remains
responsible only for Amp invocation, provider acknowledgement, and provider
execution lookup; it must not persist or expose Amp account credentials.
