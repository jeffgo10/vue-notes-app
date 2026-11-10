# Vue Notes App — agent guide

Laravel + Vue 3 + Pinia + Tailwind CSS v4 SPA for managing notes and file attachments. Local development runs via Laravel Sail (`./vendor/bin/sail`).

Do not print secrets or `.env` values.

## Skills

The same skills live in `.agents/skills/` and are linked for Claude (`.claude/skills`) and Cursor (`.cursor/skills`).

| Skill | Use when |
|---|---|
| `handoff` | Compact this session into a temp-file handoff |
| `create-github-pr` | Open a PR into `dev`, promote `dev` → `master`, hotfix, or clean up a merged branch |
| `notes-explore` | Trace a flow before changing it |
| `notes-verifier` | Check a change against Laravel, Vue, Pinia, and Tailwind v4 constraints |

Before a PR, run `./vendor/bin/sail artisan test` and `./vendor/bin/sail npm run build`.

