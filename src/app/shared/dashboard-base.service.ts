import { Injectable } from '@angular/core';
import { DomSanitizer, SafeStyle } from '@angular/platform-browser';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';

export interface DashboardItem {
  id: string;
  name: string;
  icon: string;
  category: string;
  route: string;
  color: string;
  pendingCount?: number;
  visible?: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardBaseService {
  // Color palette - shared across all dashboards
  readonly colorPalette: string[] = [
    '#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe',
    '#43e97b', '#fa709a', '#fee140', '#30cfd0', '#a8edea',
    '#ff9a9e', '#fecfef', '#fecfef', '#ffecd2', '#fcb69f'
  ];

  constructor(private sanitizer: DomSanitizer) {}

  // Security: Sanitize user input to prevent XSS
  sanitizeInput(input: string): string {
    if (!input) return '';
    return input
      .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
      .replace(/javascript:/gi, '')
      .replace(/on\w+\s*=/gi, '')
      .replace(/<iframe/gi, '')
      .replace(/<object/gi, '')
      .replace(/<embed/gi, '')
      .substring(0, 100);
  }

  // Validate color hex format
  validateColor(color: string): boolean {
    if (!color || typeof color !== 'string') return false;
    const hexPattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
    return hexPattern.test(color);
  }

  // Lighten color
  lightenColor(color: string, percent: number): string {
    if (!this.validateColor(color)) return '#ffffff';
    
    try {
      const num = parseInt(color.replace('#', ''), 16);
      if (isNaN(num)) return '#ffffff';
      
      const r = Math.min(255, Math.max(0, (num >> 16) + percent * 2.55));
      const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + percent * 2.55));
      const b = Math.min(255, Math.max(0, (num & 0x0000FF) + percent * 2.55));
      
      const result = '#' + (0x1000000 + (Math.round(r) << 16) + (Math.round(g) << 8) + Math.round(b)).toString(16).slice(1);
      return this.validateColor(result) ? result : '#ffffff';
    } catch (e) {
      console.error('Error lightening color:', e);
      return '#ffffff';
    }
  }

  // Darken color
  darkenColor(color: string, percent: number): string {
    if (!this.validateColor(color)) return '#000000';
    
    try {
      const num = parseInt(color.replace('#', ''), 16);
      if (isNaN(num)) return '#000000';
      
      const r = Math.max(0, Math.min(255, (num >> 16) - percent * 2.55));
      const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) - percent * 2.55));
      const b = Math.max(0, Math.min(255, (num & 0x0000FF) - percent * 2.55));
      
      const result = '#' + (0x1000000 + (Math.round(r) << 16) + (Math.round(g) << 8) + Math.round(b)).toString(16).slice(1);
      return this.validateColor(result) ? result : '#000000';
    } catch (e) {
      console.error('Error darkening color:', e);
      return '#000000';
    }
  }

  // Create animated gradient background
  createBackgroundGradient(baseColor: string): SafeStyle | null {
    if (!this.validateColor(baseColor)) return null;
    
    const lightColor1 = this.lightenColor(baseColor, 40);
    const lightColor2 = this.lightenColor(baseColor, 20);
    const darkColor1 = this.darkenColor(baseColor, 15);
    const darkColor2 = this.darkenColor(baseColor, 30);
    
    const gradient = `linear-gradient(135deg, 
      ${baseColor} 0%, 
      ${lightColor1} 15%, 
      ${lightColor2} 30%, 
      ${baseColor} 45%, 
      ${darkColor1} 60%, 
      ${baseColor} 75%, 
      ${darkColor2} 90%, 
      ${baseColor} 100%)`;
    
    return this.sanitizer.bypassSecurityTrustStyle(gradient);
  }

  // Filter items by search query and category
  filterItems(items: DashboardItem[], searchQuery: string, selectedCategory: string): DashboardItem[] {
    let filtered = [...items];
    
    // Apply search filter
    if (searchQuery && searchQuery.trim() !== '') {
      const query = this.sanitizeInput(searchQuery).toLowerCase();
      filtered = filtered.filter(item =>
        item.name.toLowerCase().includes(query) ||
        item.category.toLowerCase().includes(query)
      );
    }
    
    // Apply category filter
    if (selectedCategory && selectedCategory !== 'All') {
      filtered = filtered.filter(item => item.category === selectedCategory);
    }
    
    return filtered;
  }

  // Get unique categories from items
  getCategories(items: DashboardItem[]): string[] {
    return ['All', ...new Set(items.map(item => item.category))];
  }

  // Get category icon
  getCategoryIcon(category: string): string {
    const icons: { [key: string]: string } = {
      'All': 'fas fa-th',
      'Management': 'fas fa-briefcase',
      'Reports': 'fas fa-chart-bar',
      'Analytics': 'fas fa-chart-pie',
      'Operations': 'fas fa-cogs',
      'Inventory': 'fas fa-warehouse',
      'Account': 'fas fa-book',
      'Sales': 'fas fa-shopping-bag',
      'Purchase': 'fas fa-shopping-cart',
      'Stock': 'fas fa-warehouse',
      'Bank': 'fas fa-university',
      'Masters': 'fas fa-cog',
      'HR': 'fas fa-user-tie',
      'Quality': 'fas fa-check-circle',
      'Production': 'fas fa-industry',
      'Engineering': 'fas fa-tools',
      'IT': 'fas fa-laptop-code',
      'EHS': 'fas fa-shield-alt',
      'Security': 'fas fa-shield-alt',
      'Planning': 'fas fa-tasks',
      'Packing': 'fas fa-box',
      'QC': 'fas fa-vial',
      'IPQC': 'fas fa-clipboard-check',
      'QA': 'fas fa-check-square',
      'MRP': 'fas fa-warehouse',
      'Store': 'fas fa-archive',
      'Dispatch': 'fas fa-shipping-fast',
      'Master': 'fas fa-cog'
    };
    return icons[category] || 'fas fa-folder';
  }

  // Convert category to CSS class name
  getCardClass(category: string): string {
    const normalized = category.toLowerCase().replace(/\s+/g, '-');
    return `card-${normalized}`;
  }

  // Load user preferences
  loadUserPreferences(storageKey: string): string {
    try {
      const savedColor = localStorage.getItem(storageKey);
      if (savedColor && this.validateColor(savedColor)) {
        return savedColor;
      }
    } catch (e) {
      console.error('Error loading user preferences:', e);
    }
    return '#667eea'; // Default color
  }

  // Save user preferences
  saveUserPreferences(storageKey: string, color: string): void {
    try {
      if (this.validateColor(color)) {
        localStorage.setItem(storageKey, color);
      }
    } catch (e) {
      console.error('Error saving user preferences:', e);
    }
  }

  // Create search subject with debounce
  createSearchSubject(destroy$: Subject<void>) {
    const searchSubject = new Subject<string>();
    searchSubject.pipe(
      debounceTime(300),
      distinctUntilChanged()
    ).subscribe();
    return searchSubject;
  }
}
