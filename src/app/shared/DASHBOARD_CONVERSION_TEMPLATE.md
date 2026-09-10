# Dashboard Conversion Template

## Quick Conversion Steps for Each Dashboard

### Step 1: Update TypeScript Component

1. **Add imports:**
```typescript
import { Component, OnInit, OnDestroy, ChangeDetectorRef, ChangeDetectionStrategy } from '@angular/core';
import { DomSanitizer, SafeStyle } from '@angular/platform-browser';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil, debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { DashboardBaseService, DashboardItem } from 'src/app/shared/dashboard-base.service';
```

2. **Update @Component decorator:**
```typescript
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: [
    '../../shared/dashboard-common.styles.css',
    './dashboard.component.css'
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
```

3. **Add implements OnDestroy:**
```typescript
export class DashboardComponent implements OnInit, OnDestroy {
```

4. **Add properties:**
```typescript
  // New features
  searchQuery: string = '';
  searchSubject = new Subject<string>();
  filteredItems: DashboardItem[] = [];
  selectedCategory: string = 'All';
  showColorPalette: boolean = false;
  selectedBackgroundColor: string = '#667eea';
  customBackgroundGradient: SafeStyle | null = null;
  private destroy$ = new Subject<void>();
  
  // Dashboard items
  dashboardItems: DashboardItem[] = [];
  
  // Color palette - will be set from service
  colorPalette: string[] = [];
```

5. **Update constructor:**
```typescript
  constructor(
    private service: DataAccessService,
    private sanitizer: DomSanitizer,
    private cdr: ChangeDetectorRef,
    private router: Router,
    private baseService: DashboardBaseService
  ) {
    // Existing constructor code
    this.colorPalette = this.baseService.colorPalette;
  }
```

6. **Update ngOnInit:**
```typescript
  ngOnInit() {
    this.initializeDashboardItems();
    
    // Initialize search with debounce
    this.searchSubject.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      takeUntil(this.destroy$)
    ).subscribe(() => {
      this.filterItems();
    });
    
    // Load saved preferences
    this.loadUserPreferences();
    this.updateBackgroundGradient();
    
    // Existing ngOnInit code here
    
    this.cdr.markForCheck();
  }
  
  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
```

7. **Add required methods:**
```typescript
  initializeDashboardItems() {
    // Populate dashboardItems array based on existing HTML cards
    this.dashboardItems = [
      { id: 'item1', name: 'Item 1', icon: 'fas fa-icon', category: 'Category1', route: 'route1', color: '#FF6B6B' },
      // ... more items
    ];
    this.filteredItems = [...this.dashboardItems];
  }

  onSearchInput(event: Event) {
    const target = event.target as HTMLInputElement;
    this.searchQuery = this.baseService.sanitizeInput(target.value);
    this.searchSubject.next(this.searchQuery);
  }
  
  filterItems() {
    this.filteredItems = this.baseService.filterItems(
      this.dashboardItems,
      this.searchQuery,
      this.selectedCategory
    );
    this.cdr.markForCheck();
  }
  
  getCategories(): string[] {
    return this.baseService.getCategories(this.dashboardItems);
  }
  
  filterByCategory(category: string) {
    this.selectedCategory = category;
    this.filterItems();
  }
  
  getCategoryIcon(category: string): string {
    return this.baseService.getCategoryIcon(category);
  }
  
  getCardClass(category: string): string {
    return this.baseService.getCardClass(category);
  }
  
  toggleColorPalette() {
    this.showColorPalette = !this.showColorPalette;
    this.cdr.markForCheck();
  }
  
  selectBackgroundColor(color: string) {
    if (!this.baseService.validateColor(color)) {
      console.warn('Invalid color format:', color);
      return;
    }
    
    this.selectedBackgroundColor = color;
    this.updateBackgroundGradient();
    this.saveUserPreferences();
    
    setTimeout(() => this.cdr.markForCheck(), 0);
    setTimeout(() => this.cdr.markForCheck(), 100);
    setTimeout(() => this.cdr.markForCheck(), 300);
  }
  
  updateBackgroundGradient() {
    this.customBackgroundGradient = this.baseService.createBackgroundGradient(
      this.selectedBackgroundColor
    );
    this.cdr.markForCheck();
  }
  
  loadUserPreferences() {
    this.selectedBackgroundColor = this.baseService.loadUserPreferences('[DASHBOARD_NAME]_dashboard_bg_color');
  }
  
  saveUserPreferences() {
    this.baseService.saveUserPreferences('[DASHBOARD_NAME]_dashboard_bg_color', this.selectedBackgroundColor);
  }

  getPendingCount(itemId: string): number {
    // Override if dashboard has pending counts
    return 0;
  }
```

### Step 2: Update HTML Template

Replace the existing HTML with the shared template structure (see `dashboard-common.template.html`).

### Step 3: Update CSS

Replace entire CSS file with:
```css
@import '../../shared/dashboard-common.styles.css';

/* Dashboard-specific overrides */
.[DASHBOARD_NAME]-dashboard-container {
  /* Inherits all from .dashboard-container-base */
}

/* Category-specific card styles if needed */
.dashboard-card.card-category1 {
  border-left: 4px solid #color1;
}
```
