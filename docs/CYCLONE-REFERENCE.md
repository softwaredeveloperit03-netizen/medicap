# Cyclone-02062026 Reference Index (Medicap)

Use **Cyclone** (`c:\xampp\htdocs\Cyclone-02062026`) as the primary UI/UX reference for Medicap dashboards, navbar, and global theming. Use **Zuma** (`c:\xampp\htdocs\zuma`) for login and session security only.

## Quick map

| Area | Cyclone path | Medicap path (ported) |
|------|--------------|------------------------|
| Main launcher dashboard | `src/app/dashboard/` | `src/app/dashboard/` |
| Navbar | `src/app/navbar/` | `src/app/navbar/` |
| Global shell theme | `src/app/shared/dashboard-shell.theme.css` | same relative path |
| Module navy body class | `src/app/shared/module-navy.theme.css` | same |
| Professional / forms theme | `src/app/shared/app-professional-theme.css`, `app-global-forms.theme.css` | same |
| Dept hub card layout | `src/app/shared/dept-hub-dashboard-layout.css` | same |
| QC module dashboard shell | `src/app/shared/qc-module-dashboard/` | same |
| HR module theme | `src/app/hr/hr-global-page.theme.css`, `hr-module-dashboards.theme.css` | same |
| QC module theme | `src/app/qc/qc-global-page.theme.css` | same |
| Master module theme | `src/app/master/master-global-page.theme.css` | same |
| Store module theme | `src/app/store/store-global-page.theme.css` | same |
| Legacy card dashboards | N/A (Cyclone uses shell everywhere) | `src/app/shared/legacy-function-dashboard.theme.css` |

## When to copy from Cyclone

1. **Main dashboard** — `dashboard.component.{html,css,ts}`: greeting, search/filter bar, draggable cards, palette picker, loading state, empty state.
2. **Department dashboards** — Prefer `dashboard-shell` or `qc-module-dashboard-shell` HTML pattern; copy matching component from the same route path under Cyclone.
3. **Navbar** — Top bar layout, plant switcher styling, responsive menu (adapt logo/client branding for Medicap).
4. **New module pages** — Import the module’s `*-global-page.theme.css` and wrap content in `dashboard-shell` classes.
5. **Shared utilities** — `dept-hub-card.helpers.ts`, table/search helpers if Cyclone has a newer version.

## When NOT to copy from Cyclone

- **Login / re-auth / PHP auth** → use Zuma reference instead.
- **Client-specific config** — Medicap plant `1126`, client `GMP22052`; Cyclone has different plants and extra portal cards (Third Party Clients, Vendor Management, etc.).
- **Cyclone-only modules** — e.g. `sales-force`, `client-management`, `microbiology`, `accounts-finance` — only port if Medicap routing exists.

## Main dashboard pattern (Cyclone)

Key TypeScript behaviors:

- `ChangeDetectionStrategy.OnPush` + `ChangeDetectorRef.markForCheck()`
- `forkJoin` for `getrightsDashboard` + `checkIfPlantHead` with loading gate
- Cached greeting fields (`greetingText`, `userName`, `userDepartment`, `currentDateText`)
- `setFilteredDepts()` to avoid list flicker
- `defaultDeptOrder` for card sort when user has not reordered
- `getCardIconGlyphColor()` → white icons on gradient tiles
- Palette persisted in `localStorage` key `dashboard_palette`
- Card order persisted in `dashboard_card_order`

Key HTML/CSS classes:

- `dashboard-wrapper`, `dept-launcher-greeting`, `dept-launcher-controls`
- `launcher-search-wrap`, `launcher-filter-scroll`, `filter-btn`
- `dept-card`, `card-accent-bar`, `card-shine`, `dept-card-icon`
- Loading: `launcher-loading`; empty: `launcher-empty-state`

## Department dashboard shell pattern

```html
<div class="dashboard-shell">
  <div class="dashboard-shell-header">...</div>
  <div class="dashboard-shell-body">...</div>
</div>
```

QC-heavy modules may use:

```html
<app-qc-module-dashboard-shell [title]="..." [sections]="...">
</app-qc-module-dashboard-shell>
```

See `src/app/shared/qc-module-dashboard/` in both projects.

## Copy workflow (safe)

1. Locate the same route folder in Cyclone (e.g. `src/app/qc/dashboard/`).
2. Copy `*.html` and `*.css` first; diff `*.ts` for Cyclone-only imports.
3. If build fails on missing Cyclone-only symbols, restore Medicap `.ts` logic and keep Cyclone template/CSS.
4. Backups from pre-Cyclone theme work live in `_backups_pre_theme/` at project root.
5. Run `ng build` or `ng serve --port 7676` after bulk copies.

## Related references

| Project | Path | Use for |
|---------|------|---------|
| Medicap | `c:\xampp\htdocs\Medicap` | Target application |
| Cyclone | `c:\xampp\htdocs\Cyclone-02062026` | Dashboard UI, navbar, themes |
| Zuma | `c:\xampp\htdocs\zuma` | Login UI, session reauth, auth PHP |

## Medicap-specific notes

- Default plant: **1126 (Medicap)**; HO plants hidden on login.
- API: `environment.apiServerUrl` (server) or local via `localStorage api_mode = 'local'`.
- External portal cards on Cyclone main dashboard are disabled in Medicap (`canSeeExternalPanels = false`).
