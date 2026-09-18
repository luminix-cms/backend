# Routes and response contract

Every model using `LuminixModel` gets the routes below. Nothing is registered by hand.

**Neighbours:** query params for `index` -> `querying.md` · who is allowed through -> `authorization.md`
· hooks firing around a write -> `customization.md`.

## Generated routes

| Method | Route Name | URI | Action |
|---|---|---|---|
| `GET`    |`luminix.{model_alias}.index`                  | `/luminix-api/{model-plural}`                       | List (always paginated) |
| `POST`   |`luminix.{model_alias}.store`                  | `/luminix-api/{model-plural}`                       | Create           |
| `GET`    |`luminix.{model_alias}.show`                   | `/luminix-api/{model-plural}/{id}`                  | Show             |
| `POST`   |`luminix.{model_alias}.update`                 | `/luminix-api/{model-plural}/{id}`                  | Update           |
| `DELETE` |`luminix.{model_alias}.destroy`                | `/luminix-api/{model-plural}/{id}`                  | Delete one       |
| `DELETE` |`luminix.{model_alias}.destroyMany`            | `/luminix-api/{model-plural}`                       | Delete many      |
| `POST`   |`luminix.{model_alias}.restoreMany`            | `/luminix-api/{model-plural}/restore`               | Restore (soft)   |
| `POST`   |`luminix.{model_alias}.{relation_name}:sync`   | `/luminix-api/{model-plural}/{id}/{relation}/sync`  | Sync M:N         |
| `POST`   |`luminix.{model_alias}.{relation_name}:attach` | `/luminix-api/{model-plural}/{id}/{relation}/{rid}` | Attach M:N       |
| `DELETE` |`luminix.{model_alias}.{relation_name}:detach` | `/luminix-api/{model-plural}/{id}/{relation}/{rid}` | Detach M:N       |

Alias defaults to snake_case model name (`Post` → `post`, `ToDo` → `to_do`).

## Response status codes

| Situation | Status |
|---|---|
| `index` / `show` / `update` / `sync` / `attach` / `detach` success | `200` + item/page JSON |
| `store` success | `201` + item JSON |
| `destroy` / `destroyMany` / `restoreMany` success | `204` (empty body) |
| Gate (`{action}-{alias}`) denies | `401` |
| Record hidden by `scopeAllowed()` on pre-write/read lookup | `404` |
| Record doesn't exist at all / `destroyMany` matches 0 ids | `404` |
| Validation failure (incl. `per_page` > max) | `422` (standard Laravel) |

## Scope + gate contract

Pre-write lookups scoped + gated; post-write response fetch UNSCOPED (`refetchItem()`, public +
overridable). Committed write NEVER 404s — `store` always `201`, `update`/relations always `200`,
even when `scopeAllowed` hides row from own author (e.g. create/reassign for another user).

Order of checks differs per action — matters when asserting in tests:

- **`update`/`destroy`**: `findItem()` (allowed-scoped) runs FIRST, gate second → record hidden by
  `scopeAllowed` = `404` even if gate would also deny; visible record + denying gate = `401`.
- **`show`**: raw `findOrFail` first, gate second (unscoped item), scoped `findItem` last →
  nonexistent = `404`, gate denial = `401`, exists-but-scope-hidden = `404`.
- **`store`**: gate checked with `null` item (`Gate::allows('create-{alias}', [null])`) — no model
  instance exists yet; no lookup precedes the insert, and the refetch after it is unscoped.
- **`sync`/`attach`/`detach`**: authorized as UPDATE OF PARENT — parent lookup via scoped
  `findItem('update')` (hidden → `404` before write), then gate `update-{alias}` with parent
  (`401`), then write, then unscoped refetch.
