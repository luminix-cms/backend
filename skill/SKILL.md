---
name: luminix-backend
description: luminix/backend — REST API auto-generated from Eloquent models. Generated routes and status codes, index query params and filter operators, deny-first Gates and scopeAllowed, validators, lifecycle hooks, config keys. Read this before crawling vendor/luminix/backend/src.
allowed-tools: Read(.claude/skills/luminix-backend/**), Read(vendor/luminix/backend/**)
---

# `luminix/backend`

Laravel package: a whole REST API generated from Eloquent models. Add the trait, declare
`$fillable`, define the Gates -> done. Controllers, routes and serializers come from the package.

## Where to read

| Read this | When |
|---|---|
| `references/getting-started.md` | putting a first model on the API, minimum viable setup, why an endpoint is silently dead |
| `references/routes-and-contract.md` | which routes exist, status codes, order of gate/scope checks, what a committed write returns |
| `references/querying.md` | `?where[]`, `?q=`, ordering, pagination, custom operators, reusing the index pipeline elsewhere |
| `references/authorization.md` | Gates, `scopeAllowed()`, which action each endpoint checks, mass-assignment and filter exposure |
| `references/customization.md` | validation, API-only events, controller lifecycle hooks, M:N relation sync |
| `references/configuration.md` | `config/luminix/backend.php` |

## Owned elsewhere

- the boot payload and manifest built from these models -> `luminix/frontend`
- the JS client calling these routes -> `@luminix/core`, `@luminix/react`
- BI queries inheriting `scopeAllowed` -> `luminix/bi`
- the admin panel over this API -> `luminix/admin`
