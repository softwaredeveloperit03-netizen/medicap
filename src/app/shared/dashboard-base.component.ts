import { OnInit, OnDestroy, ChangeDetectorRef, ChangeDetectionStrategy } from '@angular/core';
import { DomSanitizer, SafeStyle } from '@angular/platform-browser';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil, debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { DashboardBaseService, DashboardItem } from './dashboard-base.service';

/**
 * Base class for all dashboard components
 * Extend this class to get common dashboard functionality
 */
export abstract class DashboardBaseComponent implements OnInit, OnDestroy {
  // Search and filtering
  searchQuery: string = '';
  searchSubject = new Subject<string>();
  filteredItems: DashboardItem[] = [];
  selectedCategory: string = 'All';
  
  // Color palette
  showColorPalette: boolean = false;
  selectedBackgroundColor: string = '#667eea';
  customBackgroundGradient: SafeStyle | null = null;
  colorPalette: string[] = [];
  
  // Dashboard items - must be implemented by child
  abstract dashboardItems: DashboardItem[];
  
  // Storage key for preferences - must be set by child
  abstract storageKey: string;
  
  protected destroy$ = new Subject<void>();

  constructor(
    protected baseService: DashboardBaseService,
    protected sanitizer: DomSanitizer,
    protected cdr: ChangeDetectorRef,
    protected router: Router
  ) {
    this.colorPalette = this.baseService.colorPalette;
  }

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
    
    this.cdr.markForCheck();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // Must be implemented by child to initialize dashboard items
  abstract initializeDashboardItems(): void;

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
    this.selectedBackgroundColor = this.baseService.loadUserPreferences(this.storageKey);
  }

  saveUserPreferences() {
    this.baseService.saveUserPreferences(this.storageKey, this.selectedBackgroundColor);
  }

  // Override in child if needed for pending counts
  getPendingCount(itemId: string): number {
    const item = this.dashboardItems.find(i => i.id === itemId);
    return item?.pendingCount || 0;
  }
}
