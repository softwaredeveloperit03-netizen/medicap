# Complete Dashboard Conversion Plan

## Status Summary

### ✅ Fully Converted (Using Shared Code)
1. **admin/dashboard** - ✅ Complete
2. **purchase/dashboard** - ✅ Complete  
3. **account/dashboard** - ✅ Complete (just fixed)

### 🔄 Needs Conversion (17 dashboards)

All remaining dashboards need:
1. TypeScript updates (imports, properties, methods)
2. HTML template conversion
3. CSS updates to use shared styles

## Conversion Checklist for Each Dashboard

### Step 1: TypeScript Component
- [ ] Add `DashboardBaseService` and `DashboardItem` imports
- [ ] Add `OnDestroy` to implements clause
- [ ] Change `filteredItems: any[]` → `filteredItems: DashboardItem[]`
- [ ] Change `dashboardItems` type to `DashboardItem[]`
- [ ] Remove duplicate `colorPalette` array
- [ ] Add `baseService` to constructor
- [ ] Add `ngOnDestroy()` method
- [ ] Add `initializeDashboardItems()` method
- [ ] Add all required methods (filterItems, onSearchInput, etc.)
- [ ] Update `ngOnInit()` to initialize search and preferences

### Step 2: HTML Template
- [ ] Replace with shared template structure
- [ ] Map existing cards to `dashboardItems` array
- [ ] Preserve conditional visibility logic (*ngIf)
- [ ] Add search box and category filters
- [ ] Add color palette toggle

### Step 3: CSS
- [ ] Import shared styles: `@import '../../shared/dashboard-common.styles.css';`
- [ ] Remove duplicate styles
- [ ] Add only dashboard-specific overrides
- [ ] Update container class name

## Remaining Dashboards to Convert

1. **hr/dashboard** - Has many items with conditional visibility
2. **security/dashboard** - Simple structure
3. **planning/dashboard** - Simple structure
4. **production/dashboard** - May have charts/analytics
5. **packing/dashboard** - Standard structure
6. **qc/dashboard** - Standard structure
7. **microbiology/dashboard** - Standard structure
8. **ipqc/dashboard** - Standard structure
9. **qa/dashboard** - Standard structure
10. **engineering/dashboard** - Standard structure
11. **it/dashboard** - Standard structure
12. **ehs/dashboard** - Standard structure
13. **mrp/dashboard** - Standard structure
14. **store/dashboard** - Standard structure
15. **engi-store/dashboard** - Standard structure
16. **dispatch/dashboard** - Standard structure
17. **master/dashboard** - Standard structure

## Quick Reference

**Shared Files:**
- `src/app/shared/dashboard-common.styles.css` - All CSS
- `src/app/shared/dashboard-base.service.ts` - All methods
- `src/app/shared/dashboard-base.component.ts` - Base class (optional)
- `src/app/shared/dashboard-common.template.html` - Template reference

**Conversion Template:**
- See `DASHBOARD_CONVERSION_TEMPLATE.md` for detailed steps

## Next Steps

1. Convert HR dashboard (in progress)
2. Convert security, planning, production (batch 1)
3. Convert packing, qc, microbiology, ipqc, qa (batch 2)
4. Convert engineering, it, ehs, mrp (batch 3)
5. Convert store, engi-store, dispatch, master (batch 4)

Each conversion follows the same pattern - use the template and shared code!
