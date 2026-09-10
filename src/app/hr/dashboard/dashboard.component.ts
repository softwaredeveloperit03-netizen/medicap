import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

declare let alertify: any;

export interface HrCategory {
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
  showWhen?: 'dept_head' | 'isuser' | 'trainig_cordinator';
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
  software_type = '';
  unreadrinterview = 0;
  unreadrmanreq = 0;
  isuser = 'No';
  dept_head = 'No';
  trainig_cordinator = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'hr_dashboard_category';
  private readonly paletteKey = 'hr_dashboard_palette';
  private readonly pharmaHiddenIds = new Set([
    'training',
  ]);

  readonly categories: HrCategory[] = [
    { id: 'recruitment', name: 'Recruitment', icon: 'fa-user-plus' },
    { id: 'attendance', name: 'Attendance', icon: 'fa-calendar-alt' },
    { id: 'labour', name: 'Labour', icon: 'fa-hard-hat' },
    { id: 'others', name: 'Others', icon: 'fa-folder-open' },
    { id: 'masters', name: 'Masters', icon: 'fa-database' },
  ];

  managementCards: DashCard[] = [
    { id: 'recruitment', title: 'Recruitment', description: 'Manpower requisition and interviews', route: '/hr/recruitment', icon: 'fa-users', category: 'Recruitment', gradient: G.indigo, categoryId: 'recruitment' },
    { id: 'emp-form', title: 'Employee Form', description: 'Employee onboarding and forms', route: '/hr/emp-form', icon: 'fa-user-plus', category: 'Recruitment', gradient: G.blue, categoryId: 'recruitment' },
    { id: 'employee-onboarding', title: 'Employee Onboarding', description: 'Onboard recruitment joiners', route: '/hr/employee-onboarding', icon: 'fa-user-check', category: 'Recruitment', gradient: G.teal, categoryId: 'recruitment' },
    { id: 'employees', title: 'Employee Data', description: 'View and manage employee records', route: '/hr/employees', icon: 'fa-address-book', category: 'Recruitment', gradient: G.navy, categoryId: 'recruitment' },
    { id: 'resignation', title: 'Employee Exit', description: 'Resignation and relieving', route: '/hr/resignation/res', icon: 'fa-user-minus', category: 'Recruitment', gradient: G.copper, categoryId: 'recruitment' },
    { id: 'leaves', title: 'Leave Management', description: 'Apply and approve leaves', route: '/hr/employees/leaves', icon: 'fa-calendar-times', category: 'Attendance', gradient: G.navy, categoryId: 'attendance' },
    { id: 'user', title: 'User Management', description: 'Manage system users and access', route: '/hr/user', icon: 'fa-user-cog', category: 'Attendance', gradient: G.slate, categoryId: 'attendance', showWhen: 'dept_head' },
    { id: 'labour-management', title: 'Labour Management', description: 'Contractor and labour workspace', route: '/hr/labours', icon: 'fa-hard-hat', category: 'Labour', gradient: G.amber, categoryId: 'labour' },
    { id: 'training', title: 'Induction Training', description: 'Induction and training programs', route: '/hr/training', icon: 'fa-graduation-cap', category: 'Others', gradient: G.amber, categoryId: 'others' },
    { id: 'JobResp', title: 'Job Responsibility', description: 'Job roles and responsibilities', route: '/hr/JobResp', icon: 'fa-tasks', category: 'Others', gradient: G.slate, categoryId: 'others' },
    { id: 'master-holiday', title: 'Holidays List', description: 'Manage holiday calendar', route: '/hr/master/holiday', icon: 'fa-calendar-check', category: 'Masters', gradient: G.steel, categoryId: 'masters', showWhen: 'dept_head' },
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
    this.software_type = this.service.getPlantConfigFields('software_type') || '';
  }

  ngOnInit(): void {
    this.get_rights();
    this.getNotification();
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

  getNotification(): void {
    this.service.get('notification.php?type=getRequisitionrevisionNotification').subscribe((response: any) => {
      this.unreadrmanreq = Number(response?.Pending_req) || 0;
      this.syncBadges();
      if (this.unreadrmanreq > 0 && typeof alertify !== 'undefined') {
        alertify.warning(response?.text);
      }
    });
    this.service.get('notification.php?type=getPendingInterviews').subscribe((response: any) => {
      this.unreadrinterview = Number(response?.Pending_interview) || 0;
      this.syncBadges();
      if (this.unreadrinterview > 0 && typeof alertify !== 'undefined') {
        alertify.warning(response?.text);
      }
    });
  }

  private syncBadges(): void {
    const total = this.unreadrmanreq + this.unreadrinterview;
    this.managementCards.forEach((c) => {
      if (c.id === 'recruitment') {
        c.badge = total > 0 ? total : undefined;
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
          encodeURIComponent(this.loggedInDept || 'Human Resource')
      )
      .subscribe((response: any) => {
        const r = this.service.parseEmpRightsResponse
          ? this.service.parseEmpRightsResponse(response)
          : Array.isArray(response) && response[0]
            ? response[0]
            : {};
        this.isuser = r.isuser || 'No';
        this.dept_head = r.dept_head || 'No';
        this.trainig_cordinator = r.trainig_cordinator || 'No';
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

  get currentCategory(): HrCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    if (this.software_type === 'Pharma ERP' && this.pharmaHiddenIds.has(card.id)) {
      return false;
    }
    if (card.showWhen === 'dept_head' && this.dept_head !== 'Yes') {
      return false;
    }
    if (card.showWhen === 'isuser' && this.isuser !== 'Yes') {
      return false;
    }
    if (card.showWhen === 'trainig_cordinator' && this.trainig_cordinator !== 'Yes') {
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
      console.error('HR hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: HrCategory): string {
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
