import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
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
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  navy: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
  copper: 'linear-gradient(135deg, #7c2d12 0%, #c2410c 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Dispensing of Raw Material';
  sidebarTitle = 'Dispensing Modules';
  sectionLabel = 'Dispensing Modules';
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;
  plant_id: string | null = null;
  managementCards: DashCard[] = [];

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'store_dispensing_raw_dashboard_category';
  private readonly paletteKey = 'store_dispensing_raw_dashboard_palette';

  readonly categories: HubCategory[] = [
    { id: 'requests', name: 'Requests', icon: 'fa-clipboard-list' },
    { id: 'activity', name: 'Activity', icon: 'fa-clipboard-check' },
    { id: 'records', name: 'Records', icon: 'fa-book' },
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
    private service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router,
    private deptNav: DeptNavigationService
  ) {}

  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.buildCards();

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

  private buildCards(): void {
    const awaitingRoute =
      this.plant_id === '67' || this.plant_id === '86'
        ? '/store/dispensing/raw/sprequest'
        : '/store/dispensing/raw/request';

    this.managementCards = [
      {
        id: 'awaiting',
        title: 'Awaiting Dispensing Requests',
        description: 'Pending raw material dispensing requests',
        route: awaitingRoute,
        icon: 'fa-clipboard-list',
        category: 'Requests',
        gradient: G.indigo,
        categoryId: 'requests',
      },
      {
        id: 'hold',
        title: 'On Hold Requests',
        description: 'Dispensing requests placed on hold',
        route: '/store/dispensing/raw/hold',
        icon: 'fa-pause-circle',
        category: 'Requests',
        gradient: G.amber,
        categoryId: 'requests',
      },
      {
        id: 'rejected',
        title: 'Rejected Dispensing',
        description: 'Rejected dispensing requests',
        route: '/store/dispensing/raw/rejected',
        icon: 'fa-times-circle',
        category: 'Requests',
        gradient: G.copper,
        categoryId: 'requests',
      },
      {
        id: 'activity',
        title: 'Dispensing Activity',
        description: 'Start and manage dispensing activity',
        route: '/store/dispensing/raw/activity',
        icon: 'fa-clipboard-check',
        category: 'Activity',
        gradient: G.teal,
        categoryId: 'activity',
      },
      {
        id: 'manual',
        title: 'Addl. Material Dispensing',
        description: 'Additional material dispensing',
        route: '/store/dispensing/raw/manual',
        icon: 'fa-plus-circle',
        category: 'Activity',
        gradient: G.navy,
        categoryId: 'activity',
      },
      {
        id: 'log',
        title: 'Dispensing Log',
        description: 'Dispensing transaction log',
        route: '/store/dispensing/raw/log',
        icon: 'fa-book',
        category: 'Records',
        gradient: G.blue,
        categoryId: 'records',
      },
      {
        id: 'report',
        title: 'Dispensing Report',
        description: 'Dispensing reports and exports',
        route: '/store/dispensing/raw/report',
        icon: 'fa-file-alt',
        category: 'Records',
        gradient: G.steel,
        categoryId: 'records',
      },
    ];
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
      console.error('Dispensing raw hub navigation failed:', card.route, err);
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
    this.deptNav.goBack(this.route, '/store/dispensing');
  }
}
