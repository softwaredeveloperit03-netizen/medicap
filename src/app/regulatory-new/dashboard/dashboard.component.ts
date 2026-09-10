import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface RegulatoryCategory {
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
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;
  dept_head = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'regulatory_dashboard_category';
  private readonly paletteKey = 'regulatory-new_dashboard_palette';

  readonly categories: RegulatoryCategory[] = [
    { id: 'masters', name: 'Masters', icon: 'fa-database' },
    { id: 'requests', name: 'Requests', icon: 'fa-file-signature' },
    { id: 'registration', name: 'Registration', icon: 'fa-certificate' },
    { id: 'dossier', name: 'Dossier', icon: 'fa-folder-open' },
  ];

  readonly managementCards: DashCard[] = [
    { id: 'productmstr', title: 'Product List', description: 'Regulatory product list', route: '/regulatory/productMstr', icon: 'fa-file-signature', category: 'Masters', gradient: G.indigo, categoryId: 'masters' },
    { id: 'masterdoc', title: 'Master Documents', description: 'Master documents', route: '/regulatory/masterDoc', icon: 'fa-calendar-alt', category: 'Masters', gradient: G.blue, categoryId: 'masters' },
    { id: 'dossierindex', title: 'Dossier Index Master', description: 'Dossier index master', route: '/regulatory/dossierIndex', icon: 'fa-id-card-alt', category: 'Masters', gradient: G.teal, categoryId: 'masters' },
    { id: 'marketing-enquiry', title: 'Marketing Enquiry', description: 'Marketing enquiry', route: '/regulatory/marketing_enquiry', icon: 'fa-tachometer-alt', category: 'Requests', gradient: G.amber, categoryId: 'requests' },
    { id: 'approvallicences', title: 'Approval / Licences', description: 'Approvals and licences', route: '/regulatory/approvalLicences', icon: 'fa-stamp', category: 'Requests', gradient: G.navy, categoryId: 'requests' },
    { id: 'dossierreq', title: 'Dossier/DMF Request', description: 'Dossier and DMF requests', route: '/regulatory/dossierReq', icon: 'fa-money-bill-alt', category: 'Requests', gradient: G.steel, categoryId: 'requests' },
    { id: 'marketing-technical', title: 'Docu. Req.', description: 'Document request', route: '/marketing/technical', icon: 'fa-file-alt', category: 'Requests', gradient: G.slate, categoryId: 'requests' },
    { id: 'client-doc-request', title: 'Client Document Request', description: 'Upload documents assigned from Marketing via QA Head', route: '/regulatory-new/client-doc-request', icon: 'fa-file-signature', category: 'Requests', gradient: G.copper, categoryId: 'requests' },
    { id: 'registerinit', title: 'Registration Initiation', description: 'Start registration', route: '/regulatory/registerInit', icon: 'fa-certificate', category: 'Registration', gradient: G.indigo, categoryId: 'registration' },
    { id: 'registerquery', title: 'Regulatory & Clients', description: 'Regulatory and clients', route: '/regulatory/registerquery', icon: 'fa-users', category: 'Registration', gradient: G.blue, categoryId: 'registration' },
    { id: 'registration-status', title: 'Registration Status', description: 'Registration status', route: '/regulatory/registration_status', icon: 'fa-tasks', category: 'Registration', gradient: G.teal, categoryId: 'registration' },
    { id: 'submissionhistory', title: 'Dossier Calendar', description: 'Dossier calendar', route: '/regulatory/submissionHistory', icon: 'fa-history', category: 'Registration', gradient: G.amber, categoryId: 'registration' },
    { id: 'reminder', title: 'Reminders & Renewals', description: 'Reminders and renewals', route: '/regulatory/Reminder', icon: 'fa-bell', category: 'Registration', gradient: G.copper, categoryId: 'registration' },
    { id: 'dossiercompl', title: 'Dossier Compilation', description: 'Compile dossier', route: '/regulatory/dossierCompl', icon: 'fa-exclamation-circle', category: 'Dossier', gradient: G.navy, categoryId: 'dossier' },
    { id: 'dossierreview', title: 'Dossier Review', description: 'Review dossier', route: '/regulatory/dossierReview', icon: 'fa-search', category: 'Dossier', gradient: G.steel, categoryId: 'dossier' },
    { id: 'dossierapproval', title: 'Dossier Approval', description: 'Approve dossier', route: '/regulatory/dossierApproval', icon: 'fa-thumbs-up', category: 'Dossier', gradient: G.teal, categoryId: 'dossier' },
    { id: 'dossierlog', title: 'Dossier Log', description: 'Dossier log', route: '/regulatory/dossierLog', icon: 'fa-book', category: 'Dossier', gradient: G.slate, categoryId: 'dossier' },
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
          encodeURIComponent(this.loggedInDept || 'Regulatory')
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

  get currentCategory(): RegulatoryCategory | undefined {
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
      console.error('Regulatory hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: RegulatoryCategory): string {
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
