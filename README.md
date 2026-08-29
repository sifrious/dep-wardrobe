# Wardrobe

Wardrobe is the narrow runtime-invocation boundary. Hosts construct an immutable
`RuntimeInvocation`; an allowlisted `RuntimeAdapter` invokes the selected agent and
reports output through `RuntimeObserver`. Transport, persistence, lifecycle policy,
authentication, and arbitrary command execution do not belong in this package.
