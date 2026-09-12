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

## GitHub

- Repo: https://github.com/softwaredeveloperit03-netizen/medicap  
- Remote: https://github.com/softwaredeveloperit03-netizen/medicap.git (`main`)  
- **Push/pull = update only** — never force-push or re-upload the whole codebase unless the user explicitly commands it.  
- Full rule: [`.cursor/rules/medicap-github.mdc`](.cursor/rules/medicap-github.mdc)

## Shared cross-dept modules

One URL for all departments; data scoped by `localStorage.department` — do not copy per dept.

- PM Intimation → `/preventiveimain` — [`.cursor/rules/medicap-dept-shared-modules.mdc`](.cursor/rules/medicap-dept-shared-modules.mdc)
- Equipment & Facility Work Order (FEN-001) → `/equipment-work-order` — [`.cursor/rules/medicap-equipment-work-order.mdc`](.cursor/rules/medicap-equipment-work-order.mdc)
