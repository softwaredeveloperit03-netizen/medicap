import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface HubCategory {
  id: string;
  name: string;
  icon: string;
}

export interface DashCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  categoryId: string;
}

const G = {
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  navy: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Attendance';
  sidebarTitle = 'Attendance Modules';
  sectionLabel = 'Attendance Modules';
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'hr_attendance_dashboard_category';
  private readonly paletteKey = 'hr_attendance_dashboard_palette';

  readonly categories: HubCategory[] = [
    { id: 'attendance', name: 'Attendance', icon: 'fa-calendar-check' },
    { id: 'machine', name: 'Machine', icon: 'fa-desktop' },
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'all',
      title: 'Attendance',
      description: 'View and mark attendance',
      route: '/hr/attendance/all',
      icon: 'fa-calendar-check',
      category: 'Attendance',
      gradient: G.indigo,
      categoryId: 'attendance',
    },
    {
      id: 'individual',
      title: 'Individual Attendance',
      description: 'Individual attendance records',
      route: '/hr/attendance/individual',
      icon: 'fa-user-check',
      category: 'Attendance',
      gradient: G.blue,
      categoryId: 'attendance',
    },
    {
      id: 'misspunch',
      title: 'Missed Attendance',
      description: 'Missed punch and corrections',
      route: '/hr/attendance/misspunch',
      icon: 'fa-user-clock',
      category: 'Attendance',
      gradient: G.amber,
      categoryId: 'attendance',
    },
    {
      id: 'bulk-upload',
      title: 'Bulk Upload Attendance',
      description: 'Bulk upload attendance data',
      route: '/hr/attendance/bulk-upload',
      icon: 'fa-upload',
      category: 'Attendance',
      gradient: G.steel,
      categoryId: 'attendance',
    },
    {
      id: 'machine',
      title: 'Machine Attendance',
      description: 'Machine attendance data',
      route: '/hr/attendance/MachattComponent',
      icon: 'fa-desktop',
      category: 'Machine',
      gradient: G.teal,
      categoryId: 'machine',
    },
    {
      id: 'machine-review',
      title: 'Machine Attendance Review',
      description: 'Review machine attendance',
      route: '/hr/attendance/machattereview',
      icon: 'fa-clipboard-check',
      category: 'Machine',
      gradient: G.navy,
      categoryId: 'machine',
    },
  ];

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Sky', swatch: '#e0f2fe', background: 'linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%)', cardShadow: '0 10px 30px rgba(14,165,233,0.15)' },
    { name: 'Stone', swatch: '#e7e5e4', background: 'linear-gradient(135deg, #fafaf9 0%, #e7e5e4 100%)', cardShadow: '0 10px 30px rgba(120,113,108,0.12)' },
    { name: 'Indigo', swatch: '#e0e7ff', background: 'linear-gradient(135deg, #e0e7ff 0%, #eef2ff 100%)', cardShadow: '0 10px 30px rgba(99,102,241,0.18)' },
  ];
  selectedPalette = this.paletteOptions[0];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deptNav: DeptNavigationService
  ) {}

  ngOnInit(): void {
    const fromQuery = String(this.route.snapshot.queryParamMap.get('cat') || '').trim();
    let fromStorage = '';
    try {
      fromStorage = localStorage.getItem(this.storageKey) || '';
    } catch {
      fromStorage = '';
    }
    const initial = fromQuery || fromStorage || 'all';
    if (initial === 'all' || this.categories.some((c) => c.id === initial)) {
      this.selectedCategoryId = initial;
    }
    try {
      const savedPalette = localStorage.getItem(this.paletteKey);
      if (savedPalette) {
        const found = this.paletteOptions.find((p) => p.name === savedPalette);
        if (found) this.selectedPalette = found;
      }
    } catch {
      /* ignore */
    }
  }

  @HostListener('window:resize')
  onResize(): void {
    if (this.windowWidth() >= this.sidebarBreakpointPx) {
      this.mobileSidebarOpen = false;
    }
  }

  private windowWidth(): number {
    return typeof window !== 'undefined' ? window.innerWidth : this.sidebarBreakpointPx;
  }

  get isNarrowViewport(): boolean {
    return this.windowWidth() < this.sidebarBreakpointPx;
  }

  get currentCategory(): HubCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  countForCategory(catId: string): number {
    if (catId === 'all') return this.managementCards.length;
    return this.managementCards.filter((c) => c.categoryId === catId).length;
  }

  selectCategory(catId: string): void {
    this.selectedCategoryId = catId;
    if (this.isNarrowViewport) this.mobileSidebarOpen = false;
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { cat: catId },
      queryParamsHandling: 'merge',
    });
    try {
      localStorage.setItem(this.storageKey, catId);
    } catch {
      /* ignore */
    }
  }

  showAllTabs(): void {
    this.searchQuery = '';
    this.selectCategory('all');
  }

  toggleMobileSidebar(): void {
    this.mobileSidebarOpen = !this.mobileSidebarOpen;
  }

  closeMobileSidebar(): void {
    this.mobileSidebarOpen = false;
  }

  getFilteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      const matchCat = this.selectedCategoryId === 'all' || c.categoryId === this.selectedCategoryId;
      const matchSearch =
        !q ||
        c.title.toLowerCase().includes(q) ||
        (c.description && c.description.toLowerCase().includes(q)) ||
        c.category.toLowerCase().includes(q);
      return matchCat && matchSearch;
    });
  }

  openCard(card: DashCard, event?: Event): void {
    if (event) event.preventDefault();
    void this.router.navigateByUrl(card.route).catch((err) => {
      console.error('Attendance hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: HubCategory): string {
    return cat.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem(this.paletteKey, palette.name);
    } catch {
      /* ignore */
    }
  }

  onCloseDashboard(): void {
    this.deptNav.goBack(this.route, '/hr');
  }
}
