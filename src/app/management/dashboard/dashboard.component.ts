import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

declare let alertify: any;

export interface ManagementCategory {
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
  showWhen?: 'dept_head';
  badge?: number;
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
  dept_head = 'No';
  loggedInDept: string | null = null;
  unreadExpiry = 0;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'management_dashboard_category';
  private readonly paletteKey = 'management_dashboard_palette';

  readonly categories: ManagementCategory[] = [
    { id: 'master', name: 'Master', icon: 'fa-box-open' },
    { id: 'admin', name: 'Admin', icon: 'fa-user-tie' },
    { id: 'reports', name: 'Reports', icon: 'fa-chart-line' },
    { id: 'operations', name: 'Operations', icon: 'fa-cogs' },
    { id: 'finance', name: 'Finance', icon: 'fa-coins' },
    { id: 'approvals', name: 'Approvals', icon: 'fa-user-check' },
  ];

  managementCards: DashCard[] = [
    { id: 'products', title: 'Products List', description: 'View and manage product master data', route: '/management/product', icon: 'fa-box-open', category: 'Master', gradient: G.indigo, categoryId: 'master' },
    { id: 'hr', title: 'HR Admin', description: 'HR administration and employee data', route: '/management/hr', icon: 'fa-user-tie', category: 'Admin', gradient: G.blue, categoryId: 'admin' },
    { id: 'meeting', title: 'Mgt. Review Meeting', description: 'Management review meetings', route: '/management/meeting', icon: 'fa-handshake', category: 'Admin', gradient: G.teal, categoryId: 'admin' },
    { id: 'production', title: 'Production Report', description: 'Production metrics and output reports', route: '/management/production', icon: 'fa-chart-line', category: 'Reports', gradient: G.navy, categoryId: 'reports' },
    { id: 'dispatch', title: 'Dispatch Report', description: 'Dispatch and shipment status', route: '/management/report/report', icon: 'fa-truck', category: 'Reports', gradient: G.steel, categoryId: 'reports' },
    { id: 'purchase', title: 'Purchase Report', description: 'Purchase orders and raw material logs', route: '/management/purchase', icon: 'fa-file-invoice-dollar', category: 'Reports', gradient: G.amber, categoryId: 'reports' },
    { id: 'testing', title: 'Analytical Status', description: 'Lab testing and analytical status', route: '/management/testing', icon: 'fa-vial', category: 'Reports', gradient: G.slate, categoryId: 'reports' },
    { id: 'batchrelease', title: 'Batch Release Status', description: 'Batch release and QC release logs', route: '/management/report/batchrelease', icon: 'fa-certificate', category: 'Reports', gradient: G.copper, categoryId: 'reports' },
    { id: 'shortage', title: 'Shortage Report', description: 'Material shortage and alerts', route: '/management/report/shortage', icon: 'fa-exclamation-triangle', category: 'Reports', gradient: G.indigo, categoryId: 'reports' },
    { id: 'expiry', title: 'Expiry Management', description: 'Track and manage material expiry', route: '/management/expManagement', icon: 'fa-clock', category: 'Operations', gradient: G.blue, categoryId: 'operations' },
    { id: 'inventory', title: 'Inventory', description: 'Stock and inventory overview', route: '/management/inventory', icon: 'fa-warehouse', category: 'Operations', gradient: G.teal, categoryId: 'operations' },
    { id: 'costing', title: 'Product Costing', description: 'Product cost and costing reports', route: '/management/costing', icon: 'fa-coins', category: 'Finance', gradient: G.navy, categoryId: 'finance' },
    { id: 'director-approvals', title: 'Director Approvals', description: 'VP and PO approvals', route: '/management/director', icon: 'fa-user-check', category: 'Approvals', gradient: G.steel, categoryId: 'approvals', showWhen: 'dept_head' },
    { id: 'cfo-approvals', title: 'CFO Approvals', description: 'Purchase order approvals', route: '/management/vice', icon: 'fa-file-signature', category: 'Approvals', gradient: G.amber, categoryId: 'approvals', showWhen: 'dept_head' },
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
    this.get_rights();
    this.getPendingExpiry();
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

  getPendingExpiry(): void {
    this.service.get('notification.php?type=ExpiryNotification').subscribe((response: any) => {
      this.unreadExpiry = Number(response?.Pending_expiry) || 0;
      this.syncBadges();
      if (this.unreadExpiry > 0 && typeof alertify !== 'undefined') {
        alertify.warning(response?.text);
      }
    });
  }

  private syncBadges(): void {
    this.managementCards.forEach((c) => {
      if (c.id === 'expiry') {
        c.badge = this.unreadExpiry > 0 ? this.unreadExpiry : undefined;
      }
    });
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

  get currentCategory(): ManagementCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    if (card.showWhen === 'dept_head' && this.dept_head !== 'Yes') {
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
      console.error('Management hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: ManagementCategory): string {
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
