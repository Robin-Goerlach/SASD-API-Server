---
name: Bug report
about: Report a reproducible non-security bug or documentation defect
title: "bug: "
labels: ["bug"]
assignees: []
---

> **Security:** Do NOT post vulnerabilities, API keys, passwords, internal paths, customer records, or personal health data here. Follow [SECURITY.md](../../SECURITY.md) for private reporting.
>
> **Project status:** An API server release is not yet claimed. If the problem concerns only a design document, select “Documentation/design”.

## Affected scope

- [ ] V0 design / implementation
- [ ] V1 design / implementation
- [ ] Documentation/design
- Related requirement(s), e.g. `V0-F06`:
- Related roadmap milestone/slice:

## Environment (sanitized)

- Git commit or real released version:
- PHP runtime version:
- MariaDB/MySQL or SQLite version (if relevant):
- OS, browser/client, and local vs. hosting environment:
- Routing style: `/api/v0/...` or `index.php?route=...`:

## Reproduction steps

1.
2.
3.

## Expected result

Describe expected observable behavior and its documentation reference.

## Actual result

Include HTTP status and a **sanitized** example error code or request ID. Do not paste raw authorization headers, tokens, database dumps, or sensitive logs.

## Additional checks

- [ ] Reproduced on a test instance rather than production
- [ ] Checked relevant architecture and API documentation
- [ ] Searched for an existing issue
- [ ] Confirmed the report does not disclose security-sensitive details
