# Dashboard Update Script/Checklist

## Files Created

1. **`shared/dashboard-common.styles.css`** - All shared CSS (responsive, color palette, search, cards, etc.)
2. **`shared/dashboard-base.service.ts`** - Shared service with all common methods
3. **`shared/dashboard-base.component.ts`** - Base component class (optional, for extending)
4. **`shared/dashboard-common.template.html`** - Template reference
5. **`shared/dashboard-helper.ts`** - Helper utilities

## Quick Update Process for Each Dashboard

### Step 1: Update TypeScript Component

Add to imports:
```typescript
import { DashboardBaseService } from '../../shared/dashboard-base.service';
```

Add to constructor:
```typescript
constructor(
  // ... existing services
  private baseService: DashboardBaseService
) {}
```

Replace duplicate methods with service calls:
- `sanitizeInput()` → `this.baseService.sanitizeInput()`
- `validateColor()` → `this.baseService.validateColor()`
- `filterItems()` → `this.baseService.filterItems()`
- `getCategories()` → `this.baseService.getCategories()`
- `getCategoryIcon()` → `this.baseService.getCategoryIcon()`
- `getCardClass()` → `this.baseService.getCardClass()`
- `lightenColor()` → `this.baseService.lightenColor()`
- `darkenColor()` → `this.baseService.darkenColor()`
- `createBackgroundGradient()` → `this.baseService.createBackgroundGradient()`
- `loadUserPreferences()` → `this.baseService.loadUserPreferences(storageKey)`
- `saveUserPreferences()` → `this.baseService.saveUserPreferences(storageKey, color)`

### Step 2: Update Component Decorator and CSS File

**Update TypeScript component decorator:**
```typescript
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: [
    '../../shared/dashboard-common.styles.css',
    './dashboard.component.css'
  ]
})
```

**Update CSS file** (only dashboard-specific overrides):
```css
/* Dashboard-specific overrides only */
.your-dashboard-container {
  /* Inherits all from .dashboard-container-base */
}

/* Category-specific card styles */
.dashboard-card.card-category1 {
  border-left: 4px solid #color1;
}
```

### Step 3: Update HTML Template

Use the template from `dashboard-common.template.html` and customize:
- Replace `[DASHBOARD-CONTAINER-CLASS]` with your class
- Replace `[DASHBOARD-TITLE]` with your title
- Replace `[DASHBOARD-ICON]` with your icon
- Add your header action buttons
- Add analytical section if needed

## Dashboard List to Update

- [x] admin/dashboard
- [x] account/dashboard
- [x] purchase/dashboard
- [ ] hr/dashboard
- [ ] security/dashboard
- [ ] planning/dashboard
- [ ] production/dashboard (or fproduction/dashboard)
- [ ] packing/dashboard
- [ ] qc/dashboard
- [ ] microbiology/dashboard
- [ ] ipqc/dashboard
- [ ] qa/dashboard
- [ ] engineering/dashboard
- [ ] it/dashboard
- [ ] ehs/dashboard
- [ ] mrp/dashboard
- [ ] store/dashboard
- [ ] engi-store/dashboard
- [ ] dispatch/dashboard
- [ ] master/dashboard

## Benefits

✅ **Single source of truth** - Update shared CSS once, all dashboards benefit
✅ **Consistent design** - All dashboards look and behave the same
✅ **Easier maintenance** - Fix bugs once, not 20+ times
✅ **Smaller file sizes** - Each dashboard CSS is minimal
✅ **Better performance** - Shared code is cached
