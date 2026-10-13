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
| Id missing, hidden by `scopeAllowed()`, or Gate denies an item the caller cannot read | `404`, same body for all three |
| Gate denies an item the caller can read (`read-{alias}` allows it) | `401` |
| `index`: Gate `read-{alias}` denies any row of the page · `store`: `create-{alias}` denies | `401` |
| `destroyMany` / `restoreMany`: no requested id left after lookup and Gates | `404` |
| Invalid `where[]` / `order_by` (`api.filter.throw` on) | `422` |
| Validation failure (incl. `per_page` > max, `ids` not an array of keys) | `422` (standard Laravel) |

## Scope + gate contract

Pre-write lookups scoped + gated; post-write response fetch UNSCOPED (`refetchItem()`, public +
overridable). Committed write NEVER 404s — `store` always `201`, `update`/relations always `200`,
even when `scopeAllowed` hides row from own author (e.g. create/reassign for another user).

Order of checks differs per action — matters when asserting in tests:

- **`show`/`update`/`destroy`**: `findItem()` (scoped with the action's permission) runs FIRST,
  gate second. Missing or scope-hidden = `404`. A denying gate answers `404` when `read-{alias}`
  also denies the item and `401` when it allows it, so a caller never learns that an id it cannot
  read exists. `show` checks `read` itself, so its denial is always `404`.
- **`destroyMany`/`restoreMany`**: scoped lookup of `ids`, then the gate per row. A denied row the
  caller can read = `401` for the whole request; a denied row it cannot read is dropped like a
  missing id. The write runs on the rows left; none left = `404`.
- **`store`**: gate checked with `null` item (`Gate::allows('create-{alias}', [null])`) — no model
  instance exists yet; no lookup precedes the insert, and the refetch after it is unscoped.
- **`sync`/`attach`/`detach`**: authorized as UPDATE OF PARENT — parent lookup via scoped
  `findItem('update')`, then gate `update-{alias}` with the parent (same `404`/`401` rule as
  `update`), then write, then unscoped refetch. The related ids must pass the related model's
  `scopeAllowed(permissions.index)`: `sync` answers `422` for any that does not, `attach` answers
  `404`. `detach` takes any id already in the pivot.
- **`index`**: gate `read-{alias}` per row of the page, any denial = `401`. That `401` tells the
  caller a denied row exists; only `scopeAllowed('read')` keeps rows out of a listing unseen.
