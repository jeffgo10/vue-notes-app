---
name: notes-explore
description: Read-only codebase archaeology for the Vue Notes App (Laravel + Vue 3 + Pinia + Tailwind v4). Use before implementation when tracing API routes, controllers, Eloquent models, file attachments, or Pinia state.
---

# Vue Notes App explorer

Map **how things work today** so a later change can use accurate file paths. Stay read-only during exploration — do not implement unless explicitly asked.

If an expected path does not exist, say so.

## Context anchors

| Area | Start here |
|---|---|
| Routing & Bootstrap | `bootstrap/app.php`, `routes/api.php`, `routes/web.php` |
| Backend API & Model | `app/Http/Controllers/Api/NoteController.php`, `app/Models/Note.php`, `database/migrations/` |
| Frontend Entry & Build | `vite.config.js`, `resources/views/welcome.blade.php`, `resources/js/app.js`, `resources/css/app.css` |
| Vue UI & Pinia Store | `resources/js/App.vue`, `resources/js/stores/useNoteStore.js` |
| Local Stack | `compose.yaml`, `.env.example`, `package.json`, `composer.json` |

## Exploration workflow

1. **Restate the question.**
2. **Search** — symbols, Vue components, Pinia actions, Laravel route handlers, Eloquent models.
3. **Trace** — Vue component (`resources/js/App.vue`) → Pinia store (`resources/js/stores/useNoteStore.js`) → Axios (`/api/notes`) → Laravel controller (`app/Http/Controllers/Api/NoteController.php`) → Eloquent (`app/Models/Note.php`) / Storage (`public` disk) / MySQL.
4. **Document** — files with one-line roles, gaps, and surprising dependencies.

Do not print secrets, database passwords, or `.env` values. Name the env vars only.

## Report format

```markdown
## Notes app exploration — <topic>

### Question
…

### Entry points
| File | Role |
|---|---|
| `path/…` | … |

### Data / control flow
…

### Key symbols & env vars
- …

### Gaps / unknowns
- …

### Recommended implementation touch points
| Priority | File | Why |
|---|---|---|
| 1 | `…` | … |

### Suggested next step
- Proceed with implementation, or run `notes-verifier` after it
```

Cite real workspace paths. Label **new file** when something must be created.

