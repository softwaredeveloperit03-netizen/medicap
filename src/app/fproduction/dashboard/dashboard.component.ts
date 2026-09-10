import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface ProductionCategory {
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
  showWhen?: 'ischecker';
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
  ischecker = 'No';
  dept_head = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'fproduction_dashboard_category';
  private readonly paletteKey = 'fproduction_dashboard_palette';

  readonly categories: ProductionCategory[] = [
    { id: 'planning', name: 'Planning', icon: 'fa-calendar-alt' },
    { id: 'batch', name: 'Batch', icon: 'fa-industry' },
    { id: 'reports', name: 'Reports', icon: 'fa-chart-bar' },
  ];

  readonly managementCards: DashCard[] = [
    { id: 'batch-planning', title: 'Batch Planning', description: 'Batch planning', route: '/production/batch-planning', icon: 'fa-tasks', category: 'Planning', gradient: G.indigo, categoryId: 'planning' },
    { id: 'qa-approved', title: 'QA Approved Batches', description: 'QA approved batches', route: '/production/batch/qa', icon: 'fa-check-square', category: 'Planning', gradient: G.blue, categoryId: 'planning', showWhen: 'ischecker' },
    { id: 'dispensing', title: 'Dispensing', description: 'Dispensing', route: '/production/batch/dispensing', icon: 'fa-prescription', category: 'Planning', gradient: G.teal, categoryId: 'planning' },
    { id: 'mfglines', title: 'Mfg. Lines', description: 'Manufacturing lines overview', route: '/fproduction/mfglines', icon: 'fa-industry', category: 'Planning', gradient: G.navy, categoryId: 'planning' },
    { id: 'ebmr', title: 'eBMR', description: 'eBMR', route: '/fproduction/ebmr', icon: 'fa-file-alt', category: 'Batch', gradient: G.steel, categoryId: 'batch' },
    { id: 'batch-log', title: 'Completed Batches Log', description: 'Completed batches log', route: '/production/batch/log', icon: 'fa-clipboard-list', category: 'Batch', gradient: G.slate, categoryId: 'batch' },
    { id: 'packing', title: 'Transferred for Packing', description: 'Transferred for packing', route: '/prod-f-ebmr/batch/packing', icon: 'fa-box', category: 'Batch', gradient: G.amber, categoryId: 'batch' },
    { id: 'sampling', title: 'FG Sampling', description: 'Finished goods sampling', route: '/fproduction/sampling', icon: 'fa-vial', category: 'Batch', gradient: G.teal, categoryId: 'batch' },
    { id: 'yield', title: 'Yield Reconciliation', description: 'Yield reconciliation', route: '/prod-f-ebmr/batch/yield', icon: 'fa-chart-line', category: 'Reports', gradient: G.blue, categoryId: 'reports' },
    { id: 'completed', title: 'Production Report', description: 'Production report', route: '/prod-f-ebmr/batch/completed', icon: 'fa-clipboard', category: 'Reports', gradient: G.indigo, categoryId: 'reports' },
    { id: 'technical-info', title: 'Technical Info. Sheet', description: 'Technical info sheet', route: '/prod-f-ebmr/technical-info', icon: 'fa-file-alt', category: 'Reports', gradient: G.navy, categoryId: 'reports' },
    { id: 'rejection', title: 'Rejection', description: 'Rejection', route: '/prod-f-ebmr/rejection', icon: 'fa-times-circle', category: 'Reports', gradient: G.copper, categoryId: 'reports' },
    { id: 'logbooks', title: 'Log Books', description: 'Log books', route: '/prod-f-ebmr/logbooks', icon: 'fa-book', category: 'Reports', gradient: G.steel, categoryId: 'reports' },
    { id: 'temperature', title: 'Temp. & Humidity Rec', description: 'Temperature and humidity records', route: '/fproduction/temperature', icon: 'fa-thermometer-half', category: 'Reports', gradient: G.teal, categoryId: 'reports' },
    { id: 'shortages', title: 'Shortages Calculation', description: 'Shortages calculation', route: '/prod-f-ebmr/check-shortages', icon: 'fa-exclamation-circle', category: 'Reports', gradient: G.amber, categoryId: 'reports' },
  ];

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Sky', swatch: '#e0f2fe', background: 'linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%)', cardShadow: '0 10px 30px rgba(14,165,233,0.15)' },
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
    this.get_rights();
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

  get_rights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) {
      return;
    }
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          encodeURIComponent(empId) +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || 'Production')
      )
      .subscribe((response: any) => {
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.ischecker = r.ischecker || 'No';
        this.dept_head = r.dept_head || 'No';
      });
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

  get currentCategory(): ProductionCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    if (card.showWhen === 'ischecker' && this.ischecker !== 'Yes') {
      return false;
    }
    return true;
  }

  countForCategory(catId: string): number {
    const visible = this.managementCards.filter((c) => this.isCardVisible(c));
    if (catId === 'all') {
      return visible.length;
    }
    return visible.filter((c) => c.categoryId === catId).length;
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
      if (!this.isCardVisible(c)) {
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
      console.error('Production hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: ProductionCategory): string {
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
