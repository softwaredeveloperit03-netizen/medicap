# Medicap — Cursor agent entrypoint

**Read these first** (especially on a new PC or new chat):

1. [`docs/AGENT-HANDOFF.md`](docs/AGENT-HANDOFF.md) — do / don’t, chat decisions, key paths  
2. [`docs/DEPLOY-AND-CREDENTIALS.md`](docs/DEPLOY-AND-CREDENTIALS.md) — FTP, DB, plant IDs, deploy commands  
3. [`docs/CYCLONE-REFERENCE.md`](docs/CYCLONE-REFERENCE.md) — UI reference map  

Always-on rules also live in [`.cursor/rules/`](.cursor/rules/).

## Non‑negotiables

- Deploy **only** when the user explicitly asks.
- Use `scripts/ftp_upload_medicap.py` for deploy (frontend + backend accounts differ).
- Plant `1126`, client `GMP22052`.
- Cyclone = dashboards/themes; Zuma = login/session reauth.
