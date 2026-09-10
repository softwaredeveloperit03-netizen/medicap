import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface DispatchCategory {
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
  showWhenPlantId?: string;
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
  plant_id: string | null = null;
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;
  rights: any;
  dept_head = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'dispatch_dashboard_category';
  private readonly paletteKey = 'dispatch_dashboard_palette';

  readonly categories: DispatchCategory[] = [
    { id: 'dispatch', name: 'Dispatch', icon: 'fa-truck' },
    { id: 'compliance', name: 'Compliance', icon: 'fa-file-invoice' },
    { id: 'distribution', name: 'Distribution', icon: 'fa-network-wired' },
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'spintimation',
      title: 'Intimation Received',
      description: 'Sales intimation received',
      route: '/dispatch/spintimation',
      icon: 'fa-envelope-open-text',
      category: 'Dispatch',
      gradient: G.indigo,
      categoryId: 'dispatch',
      showWhenPlantId: '67',
    },
    {
      id: 'release',
      title: 'Prod Release Status',
      description: 'Production release status',
      route: '/dispatch/release',
      icon: 'fa-check-square',
      category: 'Dispatch',
      gradient: G.blue,
      categoryId: 'dispatch',
    },
    {
      id: 'manual',
      title: 'Manual FG Entry',
      description: 'Manual finished goods entry',
      route: '/dispatch/manual',
      icon: 'fa-clipboard-check',
      category: 'Dispatch',
      gradient: G.teal,
      categoryId: 'dispatch',
    },
    {
      id: 'inventory',
      title: 'Product Inventory',
      description: 'Product inventory',
      route: '/dispatch/inventory',
      icon: 'fa-warehouse',
      category: 'Dispatch',
      gradient: G.amber,
      categoryId: 'dispatch',
    },
    {
      id: 'po',
      title: 'Client PO',
      description: 'Client purchase orders',
      route: '/dispatch/po',
      icon: 'fa-sticky-note',
      category: 'Dispatch',
      gradient: G.steel,
      categoryId: 'dispatch',
    },
    {
      id: 'sales',
      title: 'Sales Order',
      description: 'Sales orders',
      route: '/dispatch/sales',
      icon: 'fa-file-invoice',
      category: 'Dispatch',
      gradient: G.navy,
      categoryId: 'dispatch',
    },
    {
      id: 'tax',
      title: 'Tax Invoice',
      description: 'Tax invoices',
      route: '/dispatch/tax',
      icon: 'fa-file-invoice-dollar',
      category: 'Dispatch',
      gradient: G.slate,
      categoryId: 'dispatch',
    },
    {
      id: 'dispatch-report',
      title: 'Dispatch Report',
      description: 'Dispatch reports',
      route: '/dispatch/dispatch-report',
      icon: 'fa-file-alt',
      category: 'Dispatch',
      gradient: G.copper,
      categoryId: 'dispatch',
    },
    {
      id: 'closing-stock',
      title: 'Closing Stock',
      description: 'Closing stock records',
      route: '/dispatch/closing-stock',
      icon: 'fa-box',
      category: 'Dispatch',
      gradient: G.indigo,
      categoryId: 'dispatch',
    },
    {
      id: 'fg-stock-statement',
      title: 'FG Stock BOOK',
      description: 'Finished goods stock book',
      route: '/dispatch/fg-stock-statement',
      icon: 'fa-book-open',
      category: 'Dispatch',
      gradient: G.blue,
      categoryId: 'dispatch',
    },
    {
      id: 'add-product-value',
      title: 'Add Product Value',
      description: 'Add product value',
      route: '/dispatch/add-product-value',
      icon: 'fa-plus-circle',
      category: 'Dispatch',
      gradient: G.teal,
      categoryId: 'dispatch',
    },
    {
      id: 'bmr',
      title: 'BMR Log',
      description: 'BMR log',
      route: '/dispatch/bmr',
      icon: 'fa-book-medical',
      category: 'Dispatch',
      gradient: G.amber,
      categoryId: 'dispatch',
      showWhenPlantId: '67',
    },
    {
      id: 'opening-stock',
      title: 'Opening Stock',
      description: 'Opening stock',
      route: '/dispatch/opening-stock',
      icon: 'fa-box-open',
      category: 'Dispatch',
      gradient: G.steel,
      categoryId: 'dispatch',
    },
    {
      id: 'data-logger-master',
      title: 'Data logger master',
      description: 'Data logger master',
      route: '/dispatch/data-logger-master',
      icon: 'fa-database',
      category: 'Dispatch',
      gradient: G.navy,
      categoryId: 'dispatch',
    },
    {
      id: 'fg-sampling',
      title: 'FG Sampling',
      description: 'Finished goods sampling',
      route: '/dispatch/fg-sampling',
      icon: 'fa-vials',
      category: 'Dispatch',
      gradient: G.slate,
      categoryId: 'dispatch',
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
    private service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router,
    private deptNav: DeptNavigationService
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields('plant_id') || localStorage.getItem('plant_id');
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
          encodeURIComponent(this.loggedInDept || '')
      )
      .subscribe((response: any) => {
        this.rights = response;
        const r = Array.isArray(response) && response[0] ? response[0] : {};
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

  get currentCategory(): DispatchCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    if (card.showWhenPlantId && this.plant_id !== card.showWhenPlantId) {
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
      console.error('Dispatch hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: DispatchCategory): string {
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
