import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

declare let alertify: any;

interface DeptCategory {
  id: string;
  name: string;
  icon: string;
}

interface DeptCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  categoryId: string;
  gradient: string;
  showWhenDept?: string[];
  showWhenPlantId?: string;
  showWhenPlantIds?: string[];
  badgeCount?: number;
}

@Component({
  selector: 'app-deptheadhr',
  templateUrl: './deptheadhr.component.html',
  styleUrls: ['./deptheadhr.component.css'],
})
export class DeptheadhrComponent implements OnInit {
  Department: string | null = null;
  plant_id: string | null = null;
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;
  unreadAppraisal = 0;
  unreadleave = 0;
  unreadresignation = 0;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'depthead_dashboard_category';
  private readonly paletteKey = 'depthead_dashboard_palette';

  readonly categories: DeptCategory[] = [
    { id: 'hr', name: 'HR', icon: 'fa-users' },
    { id: 'operations', name: 'Operations', icon: 'fa-cogs' },
    { id: 'qms', name: 'QMS', icon: 'fa-shield-alt' },
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

  get pageTitle(): string {
    return this.Department ? `${this.Department} Dept Head` : 'Dept Head';
  }

  get currentCategory(): DeptCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  get isNarrowViewport(): boolean {
    return this.windowWidth() < this.sidebarBreakpointPx;
  }

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private router: Router,
    private deptNav: DeptNavigationService
  ) {
    this.Department = localStorage.getItem('department');
  }

  get cards(): DeptCard[] {
    const artDepts = ['Packing', 'Regulatory', 'Marketing', 'Quality Control', 'master', 'Production'];
    const base = '/hrfordepthead';
    return [
      { id: 'poApproval', title: 'Po Approval', description: 'Review and approve purchase orders', route: `${base}/poApproval`, icon: 'fa-file-signature', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhenDept: ['Purchase'], },
      { id: 'appraisal_request', title: 'Appraisal Request', description: 'Employee appraisal requests', route: `${base}/appraisal_request`, icon: 'fa-star', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', badgeCount: this.unreadAppraisal, },
      { id: 'shift_request', title: 'Shift Change Request', description: 'Approve shift change requests', route: `${base}/shift_request`, icon: 'fa-clock', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', },
      { id: 'requisition', title: 'Manpower Requisition', description: 'Manpower requisition approval', route: `${base}/requisition`, icon: 'fa-users', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', },
      { id: 'resign', title: 'Resig. Acceptance', description: 'Resignation acceptance workflow', route: `${base}/resign`, icon: 'fa-user-minus', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', badgeCount: this.unreadresignation, },
      { id: 'leave', title: 'Leave Management', description: 'Leave requests and approvals', route: `${base}/leave`, icon: 'fa-plane-departure', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)', badgeCount: this.unreadleave, },
      { id: 'duty', title: 'Outdoor Duty Form', description: 'Outdoor duty form approval', route: `${base}/duty`, icon: 'fa-person-walking', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)', },
      { id: 'Visitor-Pass', title: 'Visitor Pass', description: 'Visitor pass management', route: `${base}/visitor-pass`, icon: 'fa-id-badge', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', },
      { id: 'Outpass-Approval', title: 'Outpass Approval', description: 'Employee outpass approval', route: `${base}/outpass-approval`, icon: 'fa-door-open', category: 'HR', categoryId: 'hr', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', },
      { id: 'artwork', title: 'ArtWork', description: 'Artwork review and approval', route: `${base}/artwork`, icon: 'fa-palette', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)', showWhenDept: artDepts, },
      { id: 'qaartwork', title: 'ArtWork For QA Approval', description: 'QA artwork approval', route: `${base}/qaartwork`, icon: 'fa-image', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)', showWhenDept: ['Quality Assurance'], },
      { id: 'training', title: 'Training', description: 'Training approvals and records', route: `${base}/training`, icon: 'fa-chalkboard-user', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', },
      { id: 'jobres', title: 'Job Responsibility', description: 'Job responsibility management', route: `${base}/jobres`, icon: 'fa-briefcase', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', },
      { id: 'stockTranferReq', title: 'Stock Tranfer Req. Approval', description: 'Stock transfer request approval', route: `${base}/stockTranferReq`, icon: 'fa-right-left', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', showWhenDept: ['Quality Assurance'], },
      { id: 'stockapproval', title: 'Stock Tranfer Receiving Approval', description: 'Stock transfer receiving approval', route: `${base}/stockapproval`, icon: 'fa-warehouse', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', showWhenDept: ['Store'], },
      { id: 'indent', title: 'Purchase Requisition', description: 'Purchase requisition approval', route: `${base}/indent`, icon: 'fa-file-circle-plus', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #22c1c3 0%, #3a7bd5 100%)', },
      { id: 'calibration', title: 'Calibration', description: 'Approve department calibrations before Engineering review', route: `${base}/calibration`, icon: 'fa-crosshairs', category: 'Operations', categoryId: 'operations', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', },
      { id: 'ChangeControlQA', title: 'Change Control', description: 'Change control workflow', route: `${base}/cc`, icon: 'fa-shuffle', category: 'QMS', categoryId: 'qms', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', },
      { id: 'deviation', title: 'Deviation', description: 'Deviation management', route: `${base}/deviation`, icon: 'fa-triangle-exclamation', category: 'QMS', categoryId: 'qms', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)', },
      { id: 'sop', title: 'SOP', description: 'Standard operating procedures', route: `${base}/sop`, icon: 'fa-book', category: 'QMS', categoryId: 'qms', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)', },
      { id: 'capa', title: 'CAPA', description: 'Corrective and preventive action', route: `${base}/capa`, icon: 'fa-clipboard-check', category: 'QMS', categoryId: 'qms', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)', },
      { id: 'incident', title: 'Incident', description: 'Incident reporting and review', route: `${base}/incident`, icon: 'fa-bolt', category: 'QMS', categoryId: 'qms', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)', },
      { id: 'stages-approval', title: 'Without eBMR Production Stages & IPQC Approval', description: 'Production stages and IPQC approval', route: '/production/stages-master/approval', icon: 'fa-industry', category: 'QMS', categoryId: 'qms', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhenDept: ['Quality Assurance'], },
    ];
  }

  ngOnInit(): void {
    this.plant_id = localStorage.getItem('plant_id');
    this.Department = localStorage.getItem('department');
    this.getapprisals_log();
    this.getApprovedLeave();
    this.getData();

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

  @HostListener('window:resize')
  onResize(): void {
    if (this.windowWidth() >= this.sidebarBreakpointPx) {
      this.mobileSidebarOpen = false;
    }
  }

  private windowWidth(): number {
    return typeof window !== 'undefined' ? window.innerWidth : this.sidebarBreakpointPx;
  }

  private normalizePlantId(value: any): string {
    return value === null || value === undefined ? '' : String(value).trim();
  }

  showCard(card: DeptCard): boolean {
    const currentPlantId = this.normalizePlantId(this.plant_id);
    if (card.showWhenDept && (!this.Department || !card.showWhenDept.includes(this.Department))) {
      return false;
    }
    if (card.showWhenPlantId && currentPlantId !== this.normalizePlantId(card.showWhenPlantId)) {
      return false;
    }
    if (card.showWhenPlantIds) {
      const allowedPlantIds = card.showWhenPlantIds.map((id) => this.normalizePlantId(id));
      if (!currentPlantId || !allowedPlantIds.includes(currentPlantId)) {
        return false;
      }
    }
    return true;
  }

  countForCategory(catId: string): number {
    const visible = this.cards.filter((c) => this.showCard(c));
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

  getFilteredCards(): DeptCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter((c) => {
      if (!this.showCard(c)) {
        return false;
      }
      const matchCat = this.selectedCategoryId === 'all' || c.categoryId === this.selectedCategoryId;
      const matchSearch =
        !q ||
        c.title.toLowerCase().includes(q) ||
        c.category.toLowerCase().includes(q) ||
        (!!c.description && c.description.toLowerCase().includes(q));
      return matchCat && matchSearch;
    });
  }

  openCard(card: DeptCard, event?: Event): void {
    if (event) {
      event.preventDefault();
    }
    void this.router.navigateByUrl(card.route).catch((err) => {
      console.error('Dept Head hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DeptCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: DeptCategory): string {
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

  getapprisals_log(): void {
    this.service
      .get(
        'hr/appraisalchecklist.php?type=getapprisals_log_for_deptHeadNotification&department1=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.unreadAppraisal = response?.pending_appraisal || 0;
        if (this.unreadAppraisal > 0 && typeof alertify !== 'undefined') {
          alertify.warning(response['text']);
        }
      });
  }

  getApprovedLeave(): void {
    this.service
      .get(
        'hr/leaveForm.php?type=getPendingLeaveForDeptNotification&department_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.unreadleave = response?.pending_leaveForm || 0;
        if (this.unreadleave > 0 && typeof alertify !== 'undefined') {
          alertify.warning(response['text']);
        }
      });
  }

  getData(): void {
    this.service
      .get(
        'hr/resignation.php?type=get_resignation_by_deparetmentNotification&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.unreadresignation = response?.pending_resignation || 0;
        if (this.unreadresignation > 0 && typeof alertify !== 'undefined') {
          alertify.warning(response['text']);
        }
      });
  }
}
