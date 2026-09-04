# Trusted local-model admission policy 1.0.0

This policy controls models Wardrobe may advertise for managed automatic
installation. It is a contract and catalogue only: it does not discover,
download, install, or execute a model.

## U.S.-developed definition

For this policy, `us-developed` means that the organization responsible for
training and releasing the identified model is a United States legal entity,
and authoritative evidence identifies both that organization and its release
of the model. An organization name, model name, repository region tag, hosting
location, popularity, employee location, or runtime location is not evidence by
itself.

This classification describes developer provenance only. It does not claim
that training, data processing, inference, storage, personnel, or compute are
located in the United States. Execution location and control are represented
separately by the execution-mode decision and must be enforced by the host.

## Required evidence and fail-closed behavior

Policy 1.0.0 approves an entry for managed local installation only when it has:

1. exact model, version, upstream revision, artifact URL, byte size, and
   SHA-256;
2. named developer/releasing organization, U.S. country evidence, and explicit
   `us-developed` classification;
3. license identity/version/source, usage-policy source, notice obligations,
   and known decisions for local use, redistribution, and hosted commercial
   use;
4. at least one supported runtime, minimum and recommended memory, platform
   requirements, context size, Harmony format, tool/function calling, and
   structured-output support;
5. known decisions for local, bring-your-own-cloud, and Harness-managed-cloud
   execution; and
6. a review date and evidence references.

Missing or `unknown` origin, licensing, usage, artifact, runtime, hardware, or
required capability evidence rejects the entry. Open weights alone do not
establish commercial use, redistribution, or hosted-inference permission.
Those permissions remain independent facts even when they have the same value.
Hosted permission does not imply local installer permission.

## Deterministic installer boundary

The managed installer must call `TrustedModelCatalogue::admitArtifact()` and
accept only an exact source URL and SHA-256 pair from an approved entry. A
different mirror, mutable branch URL, revision, representation, quantization,
or digest is a different artifact and is rejected.

An explicit advanced unmanaged path may bypass this catalogue only when the
host labels it unmanaged and keeps it outside the managed-install MVP. Wardrobe
does not provide that bypass.

## Versioning and provenance

The catalogue, policy, model, upstream revision, source, and digest are
versioned independently. A Run must retain the complete value returned by
`TrustedModelCatalogue::provenanceFor()`; it must not resolve that provenance
against a newer catalogue later.

The bundled JSON must match its adjacent SHA-256 file before it is parsed.
This verifies catalogue integrity. Authenticity is inherited from the trusted
Wardrobe package/release channel; copying the JSON and checksum together into
an untrusted channel is not a signature and must not establish trust.

## Catalogue 1.0.0 decision

`openai/gpt-oss-20b` at revision
`f81fef1ddd90d214968e951a76834f1ded130a18` is approved for the exact
`original/model.safetensors` artifact recorded in catalogue 1.0.0. The
catalogue records the decision and an empty rejection-reason list. Any evidence
change requires a new catalogue version and review; it must not mutate this
entry in place.
