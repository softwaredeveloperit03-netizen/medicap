# Shared Dashboard Code

This directory contains shared code for all dashboard components to ensure consistency and reduce duplication.

## Files

### Core Files

1. **`dashboard-common.styles.css`**
   - Complete CSS for all dashboards
   - Includes: responsive design, color palette, search box, category filters, cards, animations
   - **Usage**: Import in each dashboard CSS: `@import '../../shared/dashboard-common.styles.css';`

2. **`dashboard-base.service.ts`**
   - Injectable service with all common dashboard methods
   - Methods: sanitizeInput, validateColor, filterItems, getCategories, getCategoryIcon, getCardClass, lightenColor, darkenColor, createBackgroundGradient, loadUserPreferences, saveUserPreferences
   - **Usage**: Inject in component: `constructor(private baseService: DashboardBaseService) {}`

3. **`dashboard-base.component.ts`**
   - Abstract base component class
   - Extend this for new dashboards to get all functionality automatically
   - **Usage**: `export class YourDashboard extends DashboardBaseComponent { ... }`

4. **`dashboard-helper.ts`**
   - Utility functions and constants
   - Category icon mappings, default colors, storage key helpers

### Template & Documentation

5. **`dashboard-common.template.html`**
   - HTML template reference
   - Copy and customize for each dashboard

6. **`DASHBOARD_IMPLEMENTATION_GUIDE.md`**
   - Detailed implementation guide
   - Step-by-step instructions

7. **`QUICK_UPDATE_GUIDE.md`**
   - Quick reference for updating dashboards
   - Minimal code examples

8. **`DASHBOARD_UPDATE_SCRIPT.md`**
   - Checklist for updating each dashboard
   - List of all dashboards to update

## Quick Start

### For New Dashboards:

1. **CSS**: Import shared styles
   ```css
   @import '../../shared/dashboard-common.styles.css';
   ```

2. **TypeScript**: Use shared service
   ```typescript
   constructor(private baseService: DashboardBaseService) {}
   // Use: this.baseService.filterItems(), etc.
   ```

3. **HTML**: Copy template and customize

### For Existing Dashboards:

1. Replace duplicate methods with `baseService` calls
2. Replace CSS with import + minimal overrides
3. Update HTML to use shared template structure

## Benefits

- ✅ **Consistency**: All dashboards look and behave the same
- ✅ **Maintainability**: Update once, applies everywhere  
- ✅ **Security**: Centralized input sanitization
- ✅ **Performance**: Shared code is cached
- ✅ **Smaller files**: Each dashboard CSS is minimal
