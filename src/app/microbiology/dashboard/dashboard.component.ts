import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface MicrobiologyCategory {
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
  showWhenNutraceutical?: boolean;
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
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;
  software_type = '';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'microbiology_dashboard_category';
  private readonly paletteKey = 'microbiology_dashboard_palette';

  readonly categories: MicrobiologyCategory[] = [
    { id: 'department', name: 'Department', icon: 'fa-microscope' },
    { id: 'operations', name: 'Operations', icon: 'fa-cogs' },
    { id: 'cleaning', name: 'Cleaning', icon: 'fa-broom' },
    { id: 'calibration', name: 'Calibration', icon: 'fa-balance-scale' },
  ];

  readonly managementCards: DashCard[] = [
    { id: 'testing', title: 'Testing', description: 'Raw material testing', route: '/microbiology/testing/raw', icon: 'fa-vial', category: 'Department', gradient: G.indigo, categoryId: 'department' },
    { id: 'media', title: 'Media Management', description: 'Culture media management', route: '/microbiology/media', icon: 'fa-photo-video', category: 'Department', gradient: G.blue, categoryId: 'department' },
    { id: 'growth-promotion', title: 'Growth Promotion', description: 'Growth promotion studies', route: '/microbiology/growth-promotion', icon: 'fa-flask', category: 'Department', gradient: G.teal, categoryId: 'department' },
    { id: 'culture', title: 'Culture Management', description: 'Culture management', route: '/microbiology/culture', icon: 'fa-microscope', category: 'Department', gradient: G.navy, categoryId: 'department' },
    { id: 'autoclaving', title: 'Autoclaving', description: 'Autoclave records', route: '/microbiology/autocleave', icon: 'fa-temperature-high', category: 'Department', gradient: G.amber, categoryId: 'department' },
    { id: 'incubator', title: 'Incubators', description: 'Incubator monitoring', route: '/microbiology/incubator', icon: 'fa-thermometer-quarter', category: 'Department', gradient: G.steel, categoryId: 'department' },
    { id: 'water', title: 'Water Analysis', description: 'Water analysis', route: '/microbiology/water', icon: 'fa-tint', category: 'Department', gradient: G.blue, categoryId: 'department' },
    { id: 'swap', title: 'SWAB Testing', description: 'Swab testing', route: '/microbiology/swap', icon: 'fa-vial', category: 'Department', gradient: G.indigo, categoryId: 'department' },
    { id: 'environment', title: 'Environmental Monitoring', description: 'Environmental monitoring', route: '/microbiology/environment', icon: 'fa-microscope', category: 'Department', gradient: G.teal, categoryId: 'department' },
    { id: 'fogging', title: 'Fogging', description: 'Fogging records', route: '/microbiology/fogging', icon: 'fa-cloud', category: 'Department', gradient: G.steel, categoryId: 'department' },
    { id: 'des-study', title: 'Disinfectant / Preservative Efficacy', description: 'Disinfectant efficacy study', route: '/microbiology/des-study', icon: 'fa-shield-virus', category: 'Department', gradient: G.blue, categoryId: 'department' },
    { id: 'failure-investigation', title: 'Failure Investigation OOT/OOS Micro', description: 'OOT/OOS failure investigation', route: '/microbiology/failure-investigation', icon: 'fa-exclamation-triangle', category: 'Department', gradient: G.slate, categoryId: 'department' },
    { id: 'microbiologist-qualification', title: 'Microbiologist Qualification', description: 'Microbiologist qualification', route: '/microbiology/microbiologist-qualification', icon: 'fa-user-graduate', category: 'Department', gradient: G.teal, categoryId: 'department' },
    { id: 'micro-amv', title: 'Micro AMV', description: 'Micro analytical method validation', route: '/microbiology/micro-amv', icon: 'fa-vials', category: 'Department', gradient: G.navy, categoryId: 'department' },
    { id: 'water-system-monitoring', title: 'Water System Monitoring', description: 'Water system monitoring', route: '/microbiology/water-system-monitoring', icon: 'fa-tint', category: 'Department', gradient: G.steel, categoryId: 'department' },
    { id: 'audit-trail', title: 'Audit Trail Micro', description: 'Microbiology audit trail', route: '/microbiology/audit-trail', icon: 'fa-clipboard-list', category: 'Department', gradient: G.copper, categoryId: 'department' },
    { id: 'refrigerator', title: 'Refrigerator', description: 'Refrigerator records', route: '/microbiology/refrigerator', icon: 'fa-snowflake', category: 'Operations', gradient: G.blue, categoryId: 'operations' },
    { id: 'colony-counter', title: 'Colony Counter', description: 'Colony counter records', route: '/microbiology/colony-counter', icon: 'fa-vial', category: 'Operations', gradient: G.indigo, categoryId: 'operations' },
    { id: 'laf', title: 'Laminar Air Flow', description: 'LAF monitoring', route: '/microbiology/laf', icon: 'fa-wind', category: 'Operations', gradient: G.teal, categoryId: 'operations' },
    { id: 'pressure', title: 'Diff. Pressure Rec', description: 'Differential pressure records', route: '/microbiology/pressure', icon: 'fa-tachometer-alt', category: 'Operations', gradient: G.steel, categoryId: 'operations' },
    { id: 'temperature', title: 'Temp. & Humidity Rec', description: 'Temperature and humidity records', route: '/microbiology/temperature', icon: 'fa-thermometer-half', category: 'Operations', gradient: G.amber, categoryId: 'operations', showWhenNutraceutical: false },
    { id: 'passbox', title: 'Dynamic Record', description: 'Dynamic passbox records', route: '/microbiology/passbox', icon: 'fa-shipping-fast', category: 'Operations', gradient: G.navy, categoryId: 'operations', showWhenNutraceutical: false },
    { id: 'personnel', title: 'Monitoring', description: 'Personnel monitoring', route: '/microbiology/personnel', icon: 'fa-user-check', category: 'Operations', gradient: G.slate, categoryId: 'operations', showWhenNutraceutical: false },
    { id: 'lab', title: 'Laboratory Cleaning', description: 'Laboratory cleaning', route: '/microbiology/lab', icon: 'fa-flask', category: 'Cleaning', gradient: G.blue, categoryId: 'cleaning' },
    { id: 'glassware_cleaning', title: 'Glassware Cleaning', description: 'Glassware cleaning', route: '/microbiology/glassware_cleaning', icon: 'fa-glass', category: 'Cleaning', gradient: G.indigo, categoryId: 'cleaning' },
    { id: 'HDPE', title: 'HDPE Storage Tank', description: 'HDPE storage tank', route: '/microbiology/HDPE', icon: 'fa-archive', category: 'Cleaning', gradient: G.teal, categoryId: 'cleaning' },
    { id: 'digital', title: 'Digital Colony Calibr', description: 'Digital colony counter calibration', route: '/microbiology/digital', icon: 'fa-digital-tachograph', category: 'Calibration', gradient: G.steel, categoryId: 'calibration' },
    { id: 'hp', title: 'PH Meter Calibration', description: 'pH meter calibration', route: '/microbiology/hp', icon: 'fa-flask', category: 'Calibration', gradient: G.amber, categoryId: 'calibration' },
    { id: 'micropipette', title: 'Micropipette Calibr', description: 'Micropipette calibration', route: '/microbiology/micropipette', icon: 'fa-microscope', category: 'Calibration', gradient: G.navy, categoryId: 'calibration' },
    { id: 'calibration', title: 'Balance Calibration', description: 'Balance calibration', route: '/microbiology/calibration', icon: 'fa-balance-scale', category: 'Calibration', gradient: G.blue, categoryId: 'calibration' },
    { id: 'external', title: 'External Calibration', description: 'External calibration', route: '/microbiology/external', icon: 'fa-external-link-alt', category: 'Calibration', gradient: G.teal, categoryId: 'calibration' },
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
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.software_type = this.service.getPlantConfigFields('software_type') || '';
    const fromQuery = String(this.route.snapshot.queryParamMap.get('cat') || '').trim();
    const fromStorage = (() => {
      try {
        return localStorage.getItem(this.storageKey) || '';
      } catch {
        return '';
      }
    })();
    const initial = fromQuery || fromStorage || 'all';
    if (initial === 'all' || this.categories.some((c) => c.id === initial)) {
      this.selectedCategoryId = initial;
    }
    const savedPalette = localStorage.getItem(this.paletteKey);
    if (savedPalette) {
      const found = this.paletteOptions.find((p) => p.name === savedPalette);
      if (found) {
        this.selectedPalette = found;
      }
    }
  }

  showCard(card: DashCard): boolean {
    if (card.showWhenNutraceutical === false && this.software_type === 'Nutraceutical') {
      return false;
    }
    return true;
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

  get currentCategory(): MicrobiologyCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  countForCategory(catId: string): number {
    if (catId === 'all') {
      return this.managementCards.filter((c) => this.showCard(c)).length;
    }
    return this.managementCards.filter((c) => c.categoryId === catId && this.showCard(c)).length;
  }

  selectCategory(catId: string): void {
    this.selectedCategoryId = catId;
    if (this.isNarrowViewport) {
      this.mobileSidebarOpen = false;
    }
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
    this.selectedCategoryId = 'all';
    this.searchQuery = '';
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { cat: 'all' },
      queryParamsHandling: 'merge',
    });
    try {
      localStorage.setItem(this.storageKey, 'all');
    } catch {
      /* ignore */
    }
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
      if (!this.showCard(c)) {
        return false;
      }
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
    if (event) {
      event.preventDefault();
    }
    void this.router.navigateByUrl(card.route).catch((err) => {
      console.error('Microbiology hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: MicrobiologyCategory): string {
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
    this.deptNav.goBack(this.route, '/');
  }
}
