import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface PurchaseCategory {
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
  badge?: number;
}

const G = {
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  navy: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
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

  unreadQuotations = 0;
  unreadPo = 0;
  unreadindent = 0;
  unreadVendor = 0;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'purchase_dashboard_category';
  private readonly paletteKey = 'purchase_dashboard_palette';

  readonly categories: PurchaseCategory[] = [
    { id: 'vendor', name: 'Vendor Registration', icon: 'fa-user-plus' },
    { id: 'quotation', name: 'Quotation', icon: 'fa-file-alt' },
    { id: 'indent', name: 'Requisition', icon: 'fa-list-alt' },
    { id: 'po', name: 'Purchase Order', icon: 'fa-shopping-cart' },
    { id: 'receiving', name: 'Post Receiving', icon: 'fa-truck' },
    { id: 'reports', name: 'Purchase Report', icon: 'fa-chart-bar' },
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'vendor-registration',
      title: 'Vendor Registration',
      description: 'Register new vendors',
      route: '/purchase/vendor/registration',
      icon: 'fa-user-plus',
      category: 'Vendor Registration',
      gradient: G.blue,
      categoryId: 'vendor',
    },
    {
      id: 'vendor-approval',
      title: 'Vendor For Approval',
      description: 'Vendors pending approval',
      route: '/purchase/vendor/approval',
      icon: 'fa-user-check',
      category: 'Vendor Registration',
      gradient: G.teal,
      categoryId: 'vendor',
    },
    {
      id: 'vendor-for-editing',
      title: 'Vendors From QA (Edit)',
      description: 'Correct data returned by QA, then resubmit for approval',
      route: '/purchase/vendor/for-editing',
      icon: 'fa-edit',
      category: 'Vendor Registration',
      gradient: G.amber,
      categoryId: 'vendor',
    },
    {
      id: 'vendor-log',
      title: 'Vendor Log',
      description: 'Vendor registration log',
      route: '/purchase/vendor/log',
      icon: 'fa-clipboard-list',
      category: 'Vendor Registration',
      gradient: G.slate,
      categoryId: 'vendor',
    },
    {
      id: 'vendor-approved-log',
      title: 'Approved Vendor Log',
      description: 'Approved vendor records',
      route: '/purchase/vendor/approvedVendorLog',
      icon: 'fa-check-circle',
      category: 'Vendor Registration',
      gradient: G.navy,
      categoryId: 'vendor',
    },
    {
      id: 'vendor-map-material',
      title: 'Map Material',
      description: 'Map materials to vendors',
      route: '/purchase/vendor/material/map',
      icon: 'fa-link',
      category: 'Vendor Registration',
      gradient: G.steel,
      categoryId: 'vendor',
    },
    {
      id: 'quotation-enter',
      title: 'Enter Quotation',
      description: 'Create purchase quotation',
      route: '/purchase/quotation/new',
      icon: 'fa-plus-circle',
      category: 'Quotation',
      gradient: G.blue,
      categoryId: 'quotation',
    },
    {
      id: 'quotation-comparative',
      title: 'Quotation Comparative Report',
      description: 'Compare quotations',
      route: '/purchase/quotation/comparative',
      icon: 'fa-balance-scale',
      category: 'Quotation',
      gradient: G.teal,
      categoryId: 'quotation',
    },
    {
      id: 'quotation-log',
      title: 'Quotation Log',
      description: 'Quotation records',
      route: '/purchase/quotation/log',
      icon: 'fa-clipboard-list',
      category: 'Quotation',
      gradient: G.slate,
      categoryId: 'quotation',
    },
    {
      id: 'indent-new',
      title: 'New Requisition',
      description: 'Create new requisition',
      route: '/purchase/indend/raw/new',
      icon: 'fa-plus-circle',
      category: 'Requisition',
      gradient: G.indigo,
      categoryId: 'indent',
    },
    {
      id: 'indent-rmpm',
      title: 'RM/PM Requisition Processing',
      description: 'Process RM/PM requisitions',
      route: '/purchase/indend/rmpmpo',
      icon: 'fa-cogs',
      category: 'Requisition',
      gradient: G.blue,
      categoryId: 'indent',
    },
    {
      id: 'indent-gm',
      title: 'GM Requisition Processing',
      description: 'Process GM requisitions',
      route: '/purchase/indend/raw/approval',
      icon: 'fa-user-check',
      category: 'Requisition',
      gradient: G.teal,
      categoryId: 'indent',
    },
    {
      id: 'indent-log',
      title: 'Requisitions Log',
      description: 'Requisition records',
      route: '/purchase/indend/raw',
      icon: 'fa-clipboard-list',
      category: 'Requisition',
      gradient: G.slate,
      categoryId: 'indent',
    },
    {
      id: 'indent-correction',
      title: 'Correction',
      description: 'Requisition corrections',
      route: '/purchase/indend/raw/correction',
      icon: 'fa-edit',
      category: 'Requisition',
      gradient: G.navy,
      categoryId: 'indent',
    },
    {
      id: 'po-rmpm',
      title: 'RM/PM PO from Requisition',
      description: 'Create RM/PM purchase order',
      route: '/purchase/order/raw/rmpmindend',
      icon: 'fa-plus-circle',
      category: 'Purchase Order',
      gradient: G.navy,
      categoryId: 'po',
    },
    {
      id: 'po-gm',
      title: 'GM PO from Requisition',
      description: 'Create GM purchase order',
      route: '/purchase/order/raw/indend',
      icon: 'fa-plus-circle',
      category: 'Purchase Order',
      gradient: G.blue,
      categoryId: 'po',
    },
    {
      id: 'po-review',
      title: 'PO for Review',
      description: 'Purchase orders for review',
      route: '/purchase/order/raw/approval',
      icon: 'fa-user-check',
      category: 'Purchase Order',
      gradient: G.teal,
      categoryId: 'po',
    },
    {
      id: 'po-log',
      title: 'PO Log',
      description: 'Purchase order log',
      route: '/purchase/order/raw/log',
      icon: 'fa-clipboard-list',
      category: 'Purchase Order',
      gradient: G.slate,
      categoryId: 'po',
    },
    {
      id: 'po-store-status',
      title: 'PO Status From Stores',
      description: 'Store status against PO',
      route: '/purchase/order/raw/storestatus',
      icon: 'fa-warehouse',
      category: 'Purchase Order',
      gradient: G.steel,
      categoryId: 'po',
    },
    {
      id: 'post-receiving-status',
      title: 'Post Receiving Status',
      description: 'Post receiving status',
      route: '/purchase/post-receiving-status',
      icon: 'fa-truck',
      category: 'Post Receiving Status',
      gradient: G.amber,
      categoryId: 'receiving',
    },
    {
      id: 'reports',
      title: 'Purchase Report',
      description: 'Purchase reports',
      route: '/purchase/reports',
      icon: 'fa-chart-bar',
      category: 'Purchase Report',
      gradient: G.steel,
      categoryId: 'reports',
    },
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
    this.getPurchaseNotifications();
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
        if (found) {
          this.selectedPalette = found;
        }
      }
    } catch {
      /* ignore */
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
          encodeURIComponent(this.loggedInDept || 'Purchase')
      )
      .subscribe({
        next: (response: any) => {
          const r = Array.isArray(response) && response[0] ? response[0] : {};
          this.dept_head = r.dept_head || 'No';
        },
        error: () => {
          /* hub must still render */
        },
      });
  }

  getPurchaseNotifications(): void {
    const safe = (url: string, apply: (res: any) => void) => {
      this.service.get(url).subscribe({
        next: (res: any) => {
          try {
            apply(res);
            this.syncBadges();
          } catch {
            /* ignore */
          }
        },
        error: () => {
          /* ignore */
        },
      });
    };

    safe('purchase/indent.php?type=getCheckedIndendsForNotification', (res) => {
      this.unreadindent = Number(res?.Pending_indent) || 0;
    });
    safe('purchase/po/raw.php?type=getAllPendingPOForNotification', (res) => {
      this.unreadPo = Number(res?.Pending_Po) || 0;
    });
    safe('purchase/quotation.php?type=getPendingQuotationsForNotification', (res) => {
      this.unreadQuotations = Number(res?.Pending_quatation) || 0;
    });
    safe('purchase/vendor.php?type=getPendingVendorsNotification', (res) => {
      this.unreadVendor = Number(res?.Pending_Vendor) || 0;
    });
  }

  private syncBadges(): void {
    const byId: Record<string, number> = {
      'quotation-log': this.unreadQuotations,
      'indent-rmpm': this.unreadindent,
      'indent-gm': this.unreadindent,
      'po-review': this.unreadPo,
      'vendor-approval': this.unreadVendor,
    };
    this.managementCards.forEach((c) => {
      const n = byId[c.id];
      c.badge = n != null && n > 0 ? n : undefined;
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

  get currentCategory(): PurchaseCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  countForCategory(catId: string): number {
    if (catId === 'all') {
      return this.managementCards.length;
    }
    return this.managementCards.filter((c) => c.categoryId === catId).length;
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
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    const route = (card?.route || '').trim();
    if (!route) {
      return;
    }
    const parts = route.replace(/^\//, '').split('/').filter(Boolean);
    const commands = parts.length ? ['/' + parts[0], ...parts.slice(1)] : ['/'];
    void this.router.navigate(commands).catch((err) => {
      console.error('Purchase hub navigation failed:', route, err);
      return this.router.navigateByUrl(route.startsWith('/') ? route : '/' + route);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: PurchaseCategory): string {
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
