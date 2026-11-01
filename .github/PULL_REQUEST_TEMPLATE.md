## Purpose and scope

Describe the change, target milestone (e.g. V0-M2), linked issue, and requirement ID(s).

**Out of scope:** What is deliberately not addressed?

## Design and architecture

- [ ] Keeps HTTP / Service / Repository / Adapter responsibilities separated
- [ ] Preserves deny-by-default resource, field, and method behavior
- [ ] Uses safe SQL parameters and server-controlled identifiers
- [ ] Requires no unapproved runtime dependency on Docker, Redis, or a worker
- [ ] Adds/updates an ADR if an important architectural decision changes
- [ ] Updates API/configuration documentation for observable changes

## Verification (report results accurately)

- [ ] PHP syntax/static checks executed
- [ ] Relevant unit tests executed
- [ ] Relevant database integration tests executed
- [ ] HTTP contract and error-schema tests executed
- [ ] Negative security tests executed
- [ ] Shared-hosting/restore tests executed if this change affects deployment
- [ ] **Not applicable / not yet executed checks are explicitly explained below**

### Tests performed and evidence

List commands, environment, test results, and links to CI or logs **without credentials or private data**. A checkbox may be checked only after actual execution.

### Risks and known limitations

List related risks, open questions, and any impact on older V0/V1 clients.

## Security and confidentiality

- [ ] No credentials, API keys, passwords, private user records, or confidential logs are included
- [ ] New endpoints, query operators, and fields are denied unless explicitly configured
- [ ] Error responses do not expose internal SQL, file paths, or secrets

## Documentation and release impact

- [ ] README / docs index / relevant specifications aligned if needed
- [ ] CHANGELOG updated if the change affects users
- [ ] No release or acceptance is claimed without real test evidence
