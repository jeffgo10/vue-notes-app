---
name: notes-verifier
description: Skeptical QA for Vue Notes App changes. Use after implementing API endpoints, Eloquent models, file attachments, Pinia stores, or Vue components to verify claims against the repo constraints.
---

# Vue Notes App verifier

Challenge completion claims. Do not mark work verified unless the constraints below hold. Do not implement unless a bug is proven and the parent agent asked for a fix.

## Read first

- `bootstrap/app.php`, `routes/api.php`, `routes/web.php` — API vs SPA catch-all routing
- `vite.config.js`, `resources/css/app.css` — Tailwind CSS v4 (`@tailwindcss/vite` + `@import 'tailwindcss';`)
- `.env.example` — env var names, not secret values

## Verification workflow

1. **Scope** — list what was claimed done.
2. **Diff** — `git diff` / `git status` when this is a git repo; otherwise read the files named in the claim. Flag files outside the stated scope.
3. **Constraints**
   - Tailwind CSS v4 is used via `@tailwindcss/vite` in `vite.config.js` and `@import 'tailwindcss';` in `resources/css/app.css` (do not regress to Tailwind v3 `@tailwind` directives or `tailwind.config.js`).
   - SPA catch-all route in `routes/web.php` must not swallow `/api/*` or `/storage/*` paths, and `api: __DIR__.'/../routes/api.php'` must remain registered in `bootstrap/app.php`.
   - File attachments are stored on the `public` disk (`storage/app/public/attachments`) and deleted from disk when a note is deleted or its attachment is replaced.
   - Frontend state mutations flow through Pinia (`resources/js/stores/useNoteStore.js`).
   - `.env` stays untracked. `.env.example` may be committed. Never log or expose secret `.env` values.
4. **Checks** — run `./vendor/bin/sail artisan test` and `./vendor/bin/sail npm run build`.
5. **Gaps** — TODOs, missing callers, unimplemented controller methods, broken UI bindings.

## Report format

```markdown
## Notes app verification — <short title>

**Verdict:** Verified | Gaps found | Blocked

### What was checked
- …

### Confirmed
- …

### Gaps / risks (priority order)
1. **Critical** — …
2. **Should fix** — …
3. **Nice to have** — …

### Suggested verification commands
- [ ] `./vendor/bin/sail artisan test`
- [ ] `./vendor/bin/sail npm run build`

### Follow-ups
- `create-github-pr` if the change is ready to open
```

Cite file paths. If something cannot be verified without Sail/Docker running, say **Blocked** and list what is needed.

