# Quick Dashboard Update Guide

## Using Shared Code

### Option 1: Use Shared CSS via styleUrls (Recommended)

In your dashboard component TypeScript file, update the `@Component` decorator:

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

Then in your dashboard component CSS file, only add dashboard-specific overrides:

```css
/* Your dashboard-specific overrides only */
.your-dashboard-container {
  /* Only add dashboard-specific styles here */
}

/* Category-specific card borders if needed */
.dashboard-card.card-your-category {
  border-left: 4px solid #your-color;
}
```

### Option 2: Use Shared Service in TypeScript

In your dashboard component TypeScript:

```typescript
import { DashboardBaseService } from '../../shared/dashboard-base.service';

export class YourDashboardComponent {
  constructor(private baseService: DashboardBaseService) {}
  
  // Use baseService methods instead of duplicating code:
  // - baseService.sanitizeInput()
  // - baseService.validateColor()
  // - baseService.filterItems()
  // - baseService.getCategories()
  // etc.
}
```

### Option 3: Extend Base Component (Best for New Dashboards)

```typescript
import { DashboardBaseComponent } from '../../shared/dashboard-base.component';

export class YourDashboardComponent extends DashboardBaseComponent {
  storageKey = 'your_dashboard_bg_color';
  
  initializeDashboardItems() {
    this.dashboardItems = [/* your items */];
    this.filteredItems = [...this.dashboardItems];
  }
}
```

## Quick Update Checklist

For each dashboard, you need to:

1. ✅ Update TypeScript:
   - Add imports (DomSanitizer, Router, Subject, etc.)
   - Add properties (searchQuery, filteredItems, etc.)
   - Add methods (sanitizeInput, validateColor, filterItems, etc.)
   - Initialize dashboardItems array
   - Add color palette functionality

2. ✅ Update HTML:
   - Replace container class with dashboard-container-base
   - Add color palette toggle and bar
   - Add search box and category filters
   - Update card structure to use dashboard-card class
   - Add empty state

3. ✅ Update CSS:
   - Import shared styles: `@import '../../shared/dashboard-common.styles.css';`
   - Add only dashboard-specific overrides
   - Remove duplicate responsive styles (already in shared)

## Example: Minimal Dashboard CSS

```css
@import '../../shared/dashboard-common.styles.css';

/* Only dashboard-specific styles */
.your-dashboard-container {
  /* Inherits all from .dashboard-container-base */
}

/* Category-specific card styles */
.dashboard-card.card-category1 {
  border-left: 4px solid #color1;
}

.dashboard-card.card-category2 {
  border-left: 4px solid #color2;
}
```

That's it! All responsive styles, color palette, search, etc. are in the shared file.
