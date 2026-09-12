import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

/** Sidebar categories — each card belongs to exactly one group */
export interface QaCategory {
  id: string;
  name: string;
  icon: string;
}

export interface DashCard {
  id: string;
  title: string;
  description: string;
  /** Absolute app route */
  route: string;
  icon: string;
  category: string;
  gradient: string;
  categoryId: string;
  showWhen?: 'isapprover' | 'dept_head';
}

/** Professional tile accents (match Master hub — slate / blue / teal) */
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
  isapprover = 'No';
  dept_head = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'qa_dashboard_category';
  private readonly paletteKey = 'qa_dashboard_palette';

  readonly categories: QaCategory[] = [
    { id: 'qms', name: 'QMS', icon: 'fa-clipboard-check' },
    { id: 'activities', name: 'QA Activities', icon: 'fa-microscope' },
    { id: 'requests', name: 'Requests', icon: 'fa-tags' },
    { id: 'operations', name: 'Operations', icon: 'fa-cogs' },
    { id: 'other', name: 'Other', icon: 'fa-folder-open' },
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'revision',
      title: 'Revision Request',
      description: 'Document revision requests',
      route: '/qa/revision',
      icon: 'fa-exclamation-triangle',
      category: 'QMS',
      gradient: G.indigo,
      categoryId: 'qms',
    },
    {
      id: 'qms-incident',
      title: 'Incident Reporting',
      description: 'QMS incident reporting',
      route: '/qa/qms/incident',
      icon: 'fa-biohazard',
      category: 'QMS',
      gradient: G.amber,
      categoryId: 'qms',
    },
    {
      id: 'complaints',
      title: 'Complaints Management',
      description: 'Customer and product complaints',
      route: '/qa/complaints',
      icon: 'fa-comment-alt',
      category: 'QMS',
      gradient: G.teal,
      categoryId: 'qms',
    },
    {
      id: 'capa',
      title: 'CAPA',
      description: 'Corrective and preventive action',
      route: '/qa/qms/capa/dashboard',
      icon: 'fa-tasks',
      category: 'QMS',
      gradient: G.blue,
      categoryId: 'qms',
    },
    {
      id: 'risk',
      title: 'Risk Management',
      description: 'Quality risk management',
      route: '/qa/risk',
      icon: 'fa-exclamation-triangle',
      category: 'QMS',
      gradient: G.steel,
      categoryId: 'qms',
    },
    {
      id: 'pharma-training',
      title: 'Pharma Training',
      description: 'Pharma training records',
      route: '/qa/pharma-training',
      icon: 'fa-graduation-cap',
      category: 'QMS',
      gradient: G.navy,
      categoryId: 'qms',
    },
    {
      id: 'oos',
      title: 'OOS',
      description: 'Out of specification investigations',
      route: '/qa/oos',
      icon: 'fa-times-circle',
      category: 'QMS',
      gradient: G.copper,
      categoryId: 'qms',
    },
    {
      id: 'breakdown',
      title: 'Breakdown',
      description: 'Equipment breakdown records',
      route: '/qa/breakdown',
      icon: 'fa-wrench',
      category: 'QMS',
      gradient: G.slate,
      categoryId: 'qms',
    },
    {
      id: 'recall',
      title: 'Product Recall',
      description: 'Product recall management',
      route: '/qa/recall',
      icon: 'fa-exclamation-circle',
      category: 'QMS',
      gradient: G.indigo,
      categoryId: 'qms',
    },
    {
      id: 'vendor',
      title: 'Vendor Management',
      description: 'Vendor qualification and audits',
      route: '/qa/vendor',
      icon: 'fa-truck',
      category: 'QMS',
      gradient: G.blue,
      categoryId: 'qms',
    },
    {
      id: 'batch-formula-log',
      title: 'Batch Formula Approval',
      description: 'Batch formula approval log',
      route: '/qa/batch-formula-log',
      icon: 'fa-file-alt',
      category: 'QMS',
      gradient: G.teal,
      categoryId: 'qms',
    },
    {
      id: 'rootcause',
      title: 'RCA',
      description: 'Root cause analysis',
      route: '/qa/rootcause',
      icon: 'fa-tasks',
      category: 'QMS',
      gradient: G.amber,
      categoryId: 'qms',
    },
    {
      id: 'sops',
      title: 'SOP',
      description: 'Standard operating procedures',
      route: '/qa/qms/sops',
      icon: 'fa-file-alt',
      category: 'QMS',
      gradient: G.steel,
      categoryId: 'qms',
    },
    {
      id: 'pharma-self-inspection',
      title: 'Self Inspection & Internal Audit',
      description: 'Self inspection and internal audit',
      route: '/qa/qms/pharma-self-inspection',
      icon: 'fa-clipboard-check',
      category: 'QMS',
      gradient: G.navy,
      categoryId: 'qms',
    },
    {
      id: 'document',
      title: 'Document Management',
      description: 'Controlled document management',
      route: '/qa/document',
      icon: 'fa-folder-open',
      category: 'QMS',
      gradient: G.copper,
      categoryId: 'qms',
    },
    {
      id: 'ipqa',
      title: 'IPQA',
      description: 'In-process quality assurance',
      route: '/qa/ipqa',
      icon: 'fa-microscope',
      category: 'QA Activities',
      gradient: G.blue,
      categoryId: 'activities',
    },
    {
      id: 'batchrelease',
      title: 'Batch Release',
      description: 'Batch release approvals',
      route: '/qa/batchrelease',
      icon: 'fa-certificate',
      category: 'QA Activities',
      gradient: G.teal,
      categoryId: 'activities',
    },
    {
      id: 'bmr',
      title: 'Batch Plan Approval',
      description: 'Batch manufacturing record approval',
      route: '/qa/bmr',
      icon: 'fa-file-alt',
      category: 'QA Activities',
      gradient: G.indigo,
      categoryId: 'activities',
      showWhen: 'isapprover',
    },
    {
      id: 'ebmr-ebpr-master-approval',
      title: 'eBMR eBPR Master Approval',
      description: 'eBMR and eBPR master approval',
      route: '/qa/ebmr-ebpr-master-approval',
      icon: 'fa-file-signature',
      category: 'QA Activities',
      gradient: G.slate,
      categoryId: 'activities',
    },
    {
      id: 'ebmr-under-production',
      title: 'Under Production eBMR (IPQC)',
      description: 'Under production eBMR IPQC',
      route: '/qa/ebmr/under-production',
      icon: 'fa-industry',
      category: 'QA Activities',
      gradient: G.navy,
      categoryId: 'activities',
    },
    {
      id: 'rejection',
      title: 'Rejection Management',
      description: 'Material rejection management',
      route: '/qa/rejection',
      icon: 'fa-ban',
      category: 'QA Activities',
      gradient: G.amber,
      categoryId: 'activities',
    },
    {
      id: 'controlsample',
      title: 'Control Sample',
      description: 'Control sample management',
      route: '/qa/controlsample',
      icon: 'fa-vial',
      category: 'QA Activities',
      gradient: G.steel,
      categoryId: 'activities',
    },
    {
      id: 'stability',
      title: 'Stability Management',
      description: 'Stability study management',
      route: '/qa/stability',
      icon: 'fa-chart-line',
      category: 'QA Activities',
      gradient: G.copper,
      categoryId: 'activities',
    },
    {
      id: 'approval',
      title: 'Approval',
      description: 'QA approvals',
      route: '/qa/approval',
      icon: 'fa-clipboard-check',
      category: 'QA Activities',
      gradient: G.blue,
      categoryId: 'activities',
    },
    {
      id: 'apqr',
      title: 'APQR',
      description: 'Annual product quality review',
      route: '/qa/apqr',
      icon: 'fa-chart-line',
      category: 'QA Activities',
      gradient: G.teal,
      categoryId: 'activities',
    },
    {
      id: 'equipment-cleaning-verification',
      title: 'Cleaning Validation and Routine Cleaning of Equipment',
      description: 'SOP-QA-002 · sample collection · approval before production',
      route: '/qa/equipment-cleaning-verification',
      icon: 'fa-vial',
      category: 'QA Activities',
      gradient: G.steel,
      categoryId: 'activities',
    },
    {
      id: 'label',
      title: 'Label Request',
      description: 'Label change requests',
      route: '/qa/label',
      icon: 'fa-tags',
      category: 'Requests',
      gradient: G.indigo,
      categoryId: 'requests',
    },
    {
      id: 'enviornmental',
      title: 'Environment Monitoring',
      description: 'Environmental monitoring requests',
      route: '/qa/enviornmental',
      icon: 'fa-globe',
      category: 'Requests',
      gradient: G.slate,
      categoryId: 'requests',
    },
    {
      id: 'maintenance',
      title: 'Maintenance Note',
      description: 'Maintenance note requests',
      route: '/qa/maintenance',
      icon: 'fa-tools',
      category: 'Requests',
      gradient: G.navy,
      categoryId: 'requests',
    },
    {
      id: 'label-approval',
      title: 'Label Approval',
      description: 'Label approval workflow',
      route: '/qa/label-approval',
      icon: 'fa-check-square',
      category: 'Operations',
      gradient: G.amber,
      categoryId: 'operations',
      showWhen: 'isapprover',
    },
    {
      id: 'lab-incident',
      title: 'Lab Incident',
      description: 'Laboratory incident reporting',
      route: '/qa/lab-incident',
      icon: 'fa-flask',
      category: 'Operations',
      gradient: G.steel,
      categoryId: 'operations',
    },
    {
      id: 'audit',
      title: 'Audit / Self Inspection (Legacy)',
      description: 'Legacy audit and self inspection',
      route: '/qa/audit',
      icon: 'fa-clipboard-list',
      category: 'Operations',
      gradient: G.copper,
      categoryId: 'operations',
    },
    {
      id: 'technical-document',
      title: 'Client Document Request',
      description: 'Assign QC/Regulatory and approve uploaded client documents',
      route: '/qa/technical-document/document',
      icon: 'fa-file-alt',
      category: 'Operations',
      gradient: G.blue,
      categoryId: 'operations',
    },
    {
      id: 'daily',
      title: 'Temp/Pres Diff Read',
      description: 'Temperature and pressure readings',
      route: '/qa/daily',
      icon: 'fa-thermometer-half',
      category: 'Operations',
      gradient: G.teal,
      categoryId: 'operations',
    },
    {
      id: 'soft-restriction',
      title: 'Software Restrictions',
      description: 'Software access restrictions',
      route: '/qa/soft-restriction',
      icon: 'fa-lock',
      category: 'Operations',
      gradient: G.indigo,
      categoryId: 'operations',
    },
    {
      id: 'auditreport',
      title: 'Audit Trail',
      description: 'System audit trail',
      route: '/qa/auditreport',
      icon: 'fa-file-alt',
      category: 'Other',
      gradient: G.slate,
      categoryId: 'other',
    },
    {
      id: 'artwork',
      title: 'Artwork Management',
      description: 'Artwork management',
      route: '/qa/artwork',
      icon: 'fa-folder-open',
      category: 'Other',
      gradient: G.navy,
      categoryId: 'other',
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
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.isapprover = r.isapprover || 'No';
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

  get currentCategory(): QaCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    if (card.showWhen === 'isapprover' && this.isapprover !== 'Yes') {
      return false;
    }
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
      console.error('QA hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: QaCategory): string {
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
