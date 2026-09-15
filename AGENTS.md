# Developing `luminix/backend`

`src/` is the whole product; everything else here is tests, documentation or packaging.

## Two audiences, two trees

| Tree | Written for | Language | Ships |
|---|---|---|---|
| `skill/SKILL.md` + `skill/references/` | an agent **consuming** the package in an app | English | yes |
| `AGENTS.md` (this file), `CLAUDE.md` | an agent **developing** the package | English | no |
| `README.md` | a human landing on Packagist | Portuguese | yes |
| `docs/` | a human reading at tutorial length | Portuguese | no |

`.gitattributes` decides what ships. `git archive HEAD | tar -t` must list `skill/`, and never this
file, `CLAUDE.md`, `docs/`, `workbench/` or the test config.

An app that ran `vendor:publish --tag=luminix-skill` holds a copy of `skill/`, so a fix here
reaches it on that app's next publish with `--force`.

## Writing `skill/`

- update it when a change is observable from a consuming app: a route, a status code, a config key,
  a scope, a hook, a gate name. Internal refactors leave it alone
- an API described there that `src/` does not have is a bug in `skill/`
- it describes the behaviour of this commit. What an older release did belongs to the release notes
- every sentence serves the reader's current task and says something the agent could not get from a
  glance at the repository
- describe the package, not the documentation system: no prose about where the guide ships from,
  how skills are found, or what else exists in the ecosystem. Name the neighbouring package when
  the answer lives outside this one

## Working here

There is no host app. `orchestra/testbench` builds a throwaway Laravel around the package, and
`workbench/` holds the models and migrations the tests run against.

```bash
composer test                                  # testbench package:test
composer artisan migrate                       # artisan inside the throwaway app
php vendor/bin/testbench vendor:publish --tag=luminix-skill --force
```

`composer artisan` forwards each shell word as its own argument, so an artisan command that takes
options goes through `vendor/bin/testbench` directly, as above.

## Git

- `v1.x` is the release branch; work on `feat/`/`fix/` branches and merge into it
- every push to `v1.x` runs the 10-job matrix and cuts a tag + Release
- semver comes from the commit subject: `(MAJOR)` -> major, `(MINOR)` -> minor, absence -> patch
- commit messages and branch names in português
