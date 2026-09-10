import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { forkJoin, of } from 'rxjs';
import { catchError, finalize, timeout } from 'rxjs/operators';

declare let alertify;

export interface DeptItem {
  name: string;
  displayName: string;
  icon: string;
  category: string;
  color: string;
  description: string;
  badge?: string;
  badgeIcon?: string;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  plant_head = 'No';
  quality_head = 'No';
  rights: any[] = [];
  searchQuery = '';
  selectedCategory = 'all';
  filteredDepts: DeptItem[] = [];
  isLoading = true;
  allValid = false;

  greetingText = '';
  userName = 'User';
  userDepartment = 'No Department';
  currentDateText = '';

  /** Selected palette gradient for cards - applied to border and accents */
  selectedPalette = '';
  paletteBarOpen = false;

  /** User's preferred card order (department names). Persisted in localStorage. */
  preferredOrder: string[] = [];
  private readonly STORAGE_KEY_ORDER = 'dashboard_card_order';

  private readonly defaultDeptOrder: string[] = [
    'Exports', 'Management', 'master', 'Admin', 'Reception', 'Account', 'Marketing', 'Purchase',
    'Human Resource', 'Regulatory', 'Security', 'Planning', 'Production', 'Packing',
    'Quality Control', 'Quality Assurance', 'Engineering', 'IT', 'EHS', 'Store',
    'Enginering Store', 'Dispatch', 'Plant Head', 'R AND D', 'NPD', 'E Logs', 'Q-Head',
  ];

  /** Emp IDs that always see the full Medicap department launcher. */
  private readonly MASTER_LAUNCHER_EMP_IDS = new Set([
    'master',
    'medicap',
    'admin',
    'superadmin',
    'gmpadmin',
  ]);

  /** Login departments that unlock the full launcher. */
  private readonly FULL_LAUNCHER_DEPARTMENTS = new Set([
    'master',
    'it',
    'management',
    'admin',
    'planthead',
  ]);

  /** Default icon color when no palette selected (blue) */
  readonly defaultIconColor = '#2563eb';

  readonly paletteOptions: { id: string; label: string; gradient: string; iconColor: string }[] = [
    { id: 'default', label: 'Default', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', iconColor: '#2563eb' },
    { id: 'coral', label: 'Coral', gradient: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%)', iconColor: '#dc2626' },
    { id: 'ocean', label: 'Ocean', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', iconColor: '#0284c7' },
    { id: 'forest', label: 'Forest', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', iconColor: '#059669' },
    { id: 'sunset', label: 'Sunset', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', iconColor: '#ea580c' },
    { id: 'plum', label: 'Plum', gradient: 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)', iconColor: '#7c3aed' },
    { id: 'teal', label: 'Teal', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', iconColor: '#0d9488' },
    { id: 'berry', label: 'Berry', gradient: 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)', iconColor: '#9333ea' },
    { id: 'mint', label: 'Mint', gradient: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)', iconColor: '#0d9488' },
    { id: 'lavender', label: 'Lavender', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)', iconColor: '#7c3aed' },
    { id: 'peach', label: 'Peach', gradient: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)', iconColor: '#c2410c' },
    { id: 'slate', label: 'Slate', gradient: 'linear-gradient(135deg, #485563 0%, #29323c 100%)', iconColor: '#1e293b' },
    { id: 'rose', label: 'Rose', gradient: 'linear-gradient(135deg, #f857a6 0%, #ff5858 100%)', iconColor: '#e11d48' },
    { id: 'sky', label: 'Sky', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)', iconColor: '#0369a1' },
    { id: 'amber', label: 'Amber', gradient: 'linear-gradient(135deg, #f7971e 0%, #ffd200 100%)', iconColor: '#d97706' },
    { id: 'indigo', label: 'Indigo', gradient: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)', iconColor: '#4f46e5' },
    { id: 'emerald', label: 'Emerald', gradient: 'linear-gradient(135deg, #059669 0%, #34d399 100%)', iconColor: '#047857' },
    { id: 'crimson', label: 'Crimson', gradient: 'linear-gradient(135deg, #dc2626 0%, #f97316 100%)', iconColor: '#b91c1c' }
  ];

  departments: DeptItem[] = [
    { name: 'Exports', displayName: 'Import & Export', icon: 'fa-solid fa-chart-line', category: 'administrative', color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', description: 'Business Analytics & Reports' },
    { name: 'Management', displayName: 'Management', icon: 'fa-solid fa-chart-line', category: 'administrative', color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', description: 'Business Analytics & Reports' },
    { name: 'master', displayName: 'Master', icon: 'fa-solid fa-crown', category: 'support', color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', description: 'Master Data Management', badge: 'Core', badgeIcon: 'fa-solid fa-star' },
    { name: 'Admin', displayName: 'Admin', icon: 'fa-solid fa-cogs', category: 'administrative', color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', description: 'System Administration' },
    { name: 'Reception', displayName: 'Reception', icon: 'fa-solid fa-cogs', category: 'administrative', color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', description: 'System Administration' },
    { name: 'Account', displayName: 'Account', icon: 'fa-solid fa-wallet', category: 'administrative', color: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', description: 'Financial Management' },
    { name: 'Marketing', displayName: 'Marketing', icon: 'fa-solid fa-bullhorn', category: 'operations', color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', description: 'Sales & Marketing' },
    { name: 'Purchase', displayName: 'Purchase', icon: 'fa-solid fa-shopping-cart', category: 'inventory', color: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', description: 'Procurement & Sourcing' },
    { name: 'Human Resource', displayName: 'Human Resource', icon: 'fa-solid fa-users', category: 'administrative', color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)', description: 'HR Management' },
    { name: 'Regulatory', displayName: 'Regulatory', icon: 'fa-solid fa-balance-scale', category: 'quality', color: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)', description: 'Compliance & Regulations' },
    { name: 'Security', displayName: 'Material Management', icon: 'fa-solid fa-shield-alt', category: 'administrative', color: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)', description: 'Material Management' },
    { name: 'Planning', displayName: 'Planning', icon: 'fa-solid fa-project-diagram', category: 'operations', color: 'linear-gradient(135deg, #ff8a80 0%, #ea6100 100%)', description: 'Production Planning' },
    { name: 'Production', displayName: 'Production', icon: 'fa-solid fa-industry', category: 'operations', color: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)', description: 'Manufacturing Operations' },
    { name: 'Packing', displayName: 'Packing', icon: 'fa-solid fa-box', category: 'operations', color: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)', description: 'Packaging Operations' },
    { name: 'Quality Control', displayName: 'Quality Control', icon: 'fa-solid fa-clipboard-check', category: 'quality', color: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)', description: 'QC Testing & Analysis' },
    { name: 'Quality Assurance', displayName: 'Quality Assurance', icon: 'fa-solid fa-award', category: 'quality', color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', description: 'QA & Compliance', badge: 'Critical', badgeIcon: 'fa-solid fa-shield-alt' },
    { name: 'Engineering', displayName: 'Engineering', icon: 'fa-solid fa-tools', category: 'support', color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', description: 'Maintenance & Engineering' },
    { name: 'IT', displayName: 'IT', icon: 'fa-solid fa-laptop-code', category: 'support', color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', description: 'Information Technology' },
    { name: 'EHS', displayName: 'EHS', icon: 'fa-solid fa-hard-hat', category: 'support', color: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)', description: 'Environment Health Safety' },
    { name: 'Store', displayName: 'Store', icon: 'fa-solid fa-warehouse', category: 'inventory', color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', description: 'Warehouse Management' },
    { name: 'Enginering Store', displayName: 'General Store', icon: 'fa-solid fa-warehouse', category: 'inventory', color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', description: 'Warehouse Management' },
    { name: 'Dispatch', displayName: 'Dispatch', icon: 'fa-solid fa-shipping-fast', category: 'operations', color: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)', description: 'Logistics & Dispatch' },
    { name: 'Plant Head', displayName: 'Plant Head', icon: 'fa-solid fa-user-cog', category: 'operations', color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)', description: 'Plant Management', badge: 'Admin', badgeIcon: 'fa-solid fa-user-shield' },
    // { name: 'R AND D', displayName: 'R&D', icon: 'fa-solid fa-flask', category: 'quality', color: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)', description: 'Research & Product Development' },
    { name: 'NPD', displayName: 'NPD', icon: 'fa-solid fa-lightbulb', category: 'quality', color: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)', description: 'New Product Development' },
    { name: 'E Logs', displayName: 'E-Logbook', icon: 'fa-solid fa-book', category: 'support', color: 'linear-gradient(135deg, #ff8a80 0%, #ea6100 100%)', description: 'Electronic Logbooks' },
    { name: 'Q-Head', displayName: 'Q-Head', icon: 'fa-solid fa-book', category: 'quality', color: 'linear-gradient(135deg, #ff8a80 0%, #ea6100 100%)', description: 'Quality Head' },
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  /** Medicap has no external portal cards on the main launcher. */
  get canSeeExternalPanels(): boolean {
    return false;
  }

  ngOnInit(): void {
    this.greetingText = this.buildGreeting();
    this.userName = localStorage.getItem('username') || 'User';
    // Prefer login department — clicking a dept card overwrites `department` for module context.
    let loginDept = localStorage.getItem('login_department') || '';
    if (!loginDept) {
      const storedUserDept = localStorage.getItem('user_department') || '';
      if (storedUserDept && storedUserDept !== 'All departments') {
        loginDept = storedUserDept;
      } else if (
        String(localStorage.getItem('loger_id') || '').toLowerCase() === 'master' ||
        localStorage.getItem('has_master_access') === 'Yes'
      ) {
        loginDept = 'Master';
      } else {
        loginDept = localStorage.getItem('department') || 'No Department';
      }
      try {
        localStorage.setItem('login_department', loginDept);
      } catch {
        /* ignore */
      }
    }
    this.userDepartment = loginDept;
    this.currentDateText = new Date().toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });

    if (this.isYesFlag(localStorage.getItem('plant_head'))) {
      this.plant_head = 'Yes';
    }
    if (this.isYesFlag(localStorage.getItem('quality_head'))) {
      this.quality_head = 'Yes';
    }

    try {
      const saved = localStorage.getItem(this.STORAGE_KEY_ORDER);
      if (saved) {
        this.preferredOrder = JSON.parse(saved);
      }
    } catch {
      this.preferredOrder = [];
    }

    this.loadDashboardAccess();
  }

  private loadDashboardAccess(): void {
    const empId = encodeURIComponent(localStorage.getItem('emp_id') || '');
    const depName = encodeURIComponent(localStorage.getItem('department') || '');

    forkJoin({
      rights: this.service
        .get(`hr/employee.php?type=getrightsDashboard&emp_id=${empId}&dep_name=${depName}`)
        .pipe(timeout(20000), catchError(() => of([]))),
      plant: this.service
        .get(`hr/employee.php?type=checkIfPlantHead&emp_id=${empId}`)
        .pipe(timeout(20000), catchError(() => of([]))),
    })
      .pipe(
        finalize(() => {
          if (this.isLoading) {
            this.isLoading = false;
            this.filterDepartments();
            this.cdr.markForCheck();
          }
        })
      )
      .subscribe(({ rights, plant }) => {
        this.rights = this.normalizeRightsResponse(rights);
        const anyQualityHead = this.rights.some((r: any) => this.isYesFlag(r.quality_head));
        const first = this.rights.length > 0 ? this.rights[0] : null;
        if (anyQualityHead) {
          this.quality_head = 'Yes';
        } else if (first && first.quality_head !== undefined) {
          this.quality_head = first.quality_head;
        }

        const plantRows = this.normalizeRightsResponse(plant);
        const plantRow = plantRows.length > 0 ? plantRows[0] : null;
        if (plantRow?.plant_head !== undefined) {
          this.plant_head = plantRow.plant_head;
        }
        if (plantRow?.quality_head !== undefined) {
          this.quality_head = plantRow.quality_head;
        }

        this.isLoading = false;
        this.filterDepartments();
        this.cdr.markForCheck();
      });
  }

  private normalizeRightsResponse(raw: unknown): any[] {
    if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
      const obj = raw as Record<string, unknown>;
      if (obj.status === 'invalid' || obj.status === 'error') {
        return [];
      }
      if (Array.isArray(obj.data)) {
        return obj.data as any[];
      }
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return Array.isArray(raw) ? raw : [];
  }

  private buildGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) {
      return 'Good morning';
    }
    if (hour < 17) {
      return 'Good afternoon';
    }
    return 'Good evening';
  }

  getCategoryLabel(category: string): string {
    const labels: Record<string, string> = {
      administrative: 'Administrative',
      inventory: 'Inventory',
      operations: 'Operations',
      quality: 'Quality',
      support: 'Support',
      external: 'External Panels',
    };
    return labels[category] || category;
  }

  private isYesFlag(value: any): boolean {
    if (value === null || value === undefined) return false;
    const normalized = String(value).trim().toLowerCase();
    return (
      normalized === 'yes' ||
      normalized === 'y' ||
      normalized === 'true' ||
      normalized === '1' ||
      normalized === 'on'
    );
  }

  /** Normalize any department label to dashboard card `name` (e.g. QA → Quality Assurance). */
  private resolveDashboardCardName(deptRaw: unknown): string {
    const fromApi = String(deptRaw ?? '').trim();
    if (!fromApi) {
      return '';
    }
    let s = fromApi.toLowerCase().replace(/\./g, '').replace(/&/g, ' and ');
    s = s.replace(/\s*department\s*$/i, '').trim();
    s = s.replace(/\s+/g, ' ');
    const compact = s.replace(/\s/g, '');

    const toCard: Record<string, string> = {
      qa: 'Quality Assurance',
      qualityassurance: 'Quality Assurance',
      'quality assurance': 'Quality Assurance',
      'q a': 'Quality Assurance',
      qc: 'Quality Control',
      qualitycontrol: 'Quality Control',
      'quality control': 'Quality Control',
      'q c': 'Quality Control',
      hr: 'Human Resource',
      humanresource: 'Human Resource',
      'human resources': 'Human Resource',
      'human resource': 'Human Resource',
      rnd: 'R AND D',
      'r and d': 'R AND D',
      'research and development': 'R AND D',
      'product development': 'R AND D',
      npd: 'NPD',
      'new product development': 'NPD',
      accounts: 'Account',
      account: 'Account',
      it: 'IT',
      'information technology': 'IT',
      qhead: 'Q-Head',
      'q-head': 'Q-Head',
      'q head': 'Q-Head',
      'quality head': 'Q-Head',
      security: 'Security',
      'material management': 'Security',
      'materials management': 'Security',
      materialmanagement: 'Security',
      materialsmanagement: 'Security',
      'material managment': 'Security',
      exports: 'Exports',
      'import and export': 'Exports',
      'import export': 'Exports',
      'enginering store': 'Enginering Store',
      'engineering store': 'Enginering Store',
      'general store': 'Enginering Store',
    };

    if (toCard[s]) {
      return toCard[s];
    }
    if (toCard[compact]) {
      return toCard[compact];
    }
    if (s.includes('quality assurance')) {
      return 'Quality Assurance';
    }
    if (s.includes('quality control')) {
      return 'Quality Control';
    }
    if (s.includes('material') && s.includes('management')) {
      return 'Security';
    }
    if (s === 'security' || compact === 'security') {
      return 'Security';
    }

    const byDisplay = this.departments.find((d) => {
      const disp = (d.displayName || '').trim().toLowerCase();
      const nm = (d.name || '').trim().toLowerCase();
      const raw = fromApi.toLowerCase();
      return disp === raw || nm === raw;
    });
    if (byDisplay) {
      return byDisplay.name;
    }

    const exactCard = this.departments.find(
      (d) => d.name.toLowerCase() === fromApi.toLowerCase()
    );
    if (exactCard) {
      return exactCard.name;
    }

    return fromApi.replace(/\s+/g, ' ').trim();
  }

  private rightsRowDepartmentCard(row: any): string {
    if (!row) {
      return '';
    }
    const raw = row.dashboard_department || row.department;
    return this.resolveDashboardCardName(raw) || String(raw || '').trim();
  }

  private rightsDepartmentMatches(rightDept: unknown, dashboardDeptName: string, row?: any): boolean {
    const cardFromRights = row
      ? this.rightsRowDepartmentCard(row)
      : this.resolveDashboardCardName(rightDept);
    const cardTarget = this.resolveDashboardCardName(dashboardDeptName);
    if (!cardFromRights || !cardTarget) {
      return false;
    }
    return cardFromRights.toLowerCase() === cardTarget.toLowerCase();
  }

  /** Row can open department (role Yes, approved status, or main rights row). */
  private rightsRowAllowsDeptAccess(row: any): boolean {
    if (!row || typeof row !== 'object') {
      return false;
    }
    const status = String(row.status ?? '')
      .trim()
      .toLowerCase();
    if (status === 'approve' || status === 'approved' || status === 'active') {
      return true;
    }
    if (this.isYesFlag(row.main)) {
      return true;
    }
    const flags = [
      row.isuser,
      row.ischecker,
      row.isapprover,
      row.dept_head,
      row.qms_approver,
      row.isauditor,
      row.shift_allocator,
      row.trainig_cordinator,
      row.task_assigner,
      row.plant_head,
      row.quality_head,
    ];
    return flags.some((f) => this.isYesFlag(f));
  }

  private rightsHasDepartment(deptName: string): boolean {
    const target = this.resolveDashboardCardName(deptName);
    if (!target) {
      return false;
    }
    const targetLc = target.toLowerCase();
    return (this.rights || []).some((r: any) => {
      const rowCard = this.rightsRowDepartmentCard(r);
      return rowCard.toLowerCase() === targetLc;
    });
  }

  private normalizeDeptKey(value: string): string {
    return (value || '').replace(/\s+/g, '').toLowerCase().replace(/[_-]+/g, '');
  }

  /** Master / Medicap / plant-head style users see every department card. */
  private isMasterLauncherUser(): boolean {
    const empId = (localStorage.getItem('emp_id') || localStorage.getItem('loger_id') || '')
      .trim()
      .toLowerCase();
    if (empId && this.MASTER_LAUNCHER_EMP_IDS.has(empId)) {
      return true;
    }
    const loginDept = this.normalizeDeptKey(localStorage.getItem('department') || '');
    if (loginDept && this.FULL_LAUNCHER_DEPARTMENTS.has(loginDept)) {
      return true;
    }
    if (this.isYesFlag(localStorage.getItem('dept_head')) && loginDept === 'master') {
      return true;
    }
    return false;
  }

  private hasFullLauncherAccess(): boolean {
    return (
      this.isMasterLauncherUser() ||
      this.isYesFlag(this.plant_head) ||
      this.isYesFlag(localStorage.getItem('plant_head')) ||
      (this.rights || []).some((r: any) => this.isYesFlag(r.plant_head)) ||
      (this.rights || []).some(
        (r: any) => this.normalizeDeptKey(String(r.department || '')) === 'management'
      )
    );
  }

  private canAccessDepartment(deptName: string): boolean {
    if (deptName === 'master' || deptName === 'E Logs') {
      return true;
    }
    if (this.hasFullLauncherAccess()) {
      return true;
    }
    // Always show the department the user logged in under.
    const loginDept = localStorage.getItem('department') || '';
    if (loginDept && this.resolveDashboardCardName(loginDept) === this.resolveDashboardCardName(deptName)) {
      return true;
    }
    if (deptName === 'Plant Head') {
      return this.isYesFlag(this.plant_head);
    }
    if (deptName === 'Q-Head') {
      return this.isYesFlag(this.quality_head);
    }
    if (deptName === 'R AND D' || deptName === 'NPD') {
      if (this.isYesFlag(this.quality_head)) {
        return true;
      }
      return this.rightsHasDepartment(deptName);
    }
    if (deptName === 'Day Store') {
      return (this.rights || []).some((r: any) =>
        this.resolveDashboardCardName(r.dashboard_department || r.department) === 'Store'
      );
    }
    return this.rightsHasDepartment(deptName);
  }

  filterDepartments(): void {
    let filtered = [...this.departments];
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (q) {
      filtered = filtered.filter((dept) => {
        const haystack = [
          dept.displayName,
          dept.description,
          dept.name,
          this.resolveDashboardCardName(dept.name),
        ]
          .join(' ')
          .toLowerCase();
        return (
          haystack.includes(q) ||
          (q === 'rnd' && dept.name === 'R AND D') ||
          (q === 'r&d' && dept.name === 'R AND D') ||
          (q.includes('product') && q.includes('dev') && dept.name === 'R AND D')
        );
      });
    }
    if (this.selectedCategory !== 'all') {
      filtered = filtered.filter((dept) => dept.category === this.selectedCategory);
    }
    if (!this.isLoading) {
      filtered = filtered.filter((dept) => this.canAccessDepartment(dept.name));
    } else {
      filtered = [];
    }

    this.setFilteredDepts(this.sortByPreferredOrder(filtered));
  }

  /** Update list only when visible modules actually change (prevents blink/re-animate). */
  private setFilteredDepts(next: DeptItem[]): void {
    const prevKey = this.filteredDepts.map((d) => d.name).join('\u0001');
    const nextKey = next.map((d) => d.name).join('\u0001');
    if (prevKey !== nextKey) {
      this.filteredDepts = next;
    }
    this.cdr.markForCheck();
  }

  private sortByPreferredOrder(list: DeptItem[]): DeptItem[] {
    const order = this.preferredOrder.length ? this.preferredOrder : this.defaultDeptOrder;
    const orderMap = new Map(order.map((name, i) => [name, i]));
    return [...list].sort((a, b) => {
      const ia = orderMap.has(a.name) ? orderMap.get(a.name)! : 1e9;
      const ib = orderMap.has(b.name) ? orderMap.get(b.name)! : 1e9;
      return ia - ib;
    });
  }

  onCardDrop(event: CdkDragDrop<DeptItem[]>): void {
    if (event.previousIndex === event.currentIndex) return;
    this.justDragged = true;
    moveItemInArray(this.filteredDepts, event.previousIndex, event.currentIndex);
    this.preferredOrder = this.filteredDepts.map(d => d.name);
    try {
      localStorage.setItem(this.STORAGE_KEY_ORDER, JSON.stringify(this.preferredOrder));
    } catch { }
    this.cdr.markForCheck();
  }

  filterByCategory(category: string): void {
    this.selectedCategory = category;
    this.filterDepartments();
  }

  clearSearch(): void {
    this.searchQuery = '';
    this.filterDepartments();
  }

  trackByDeptName(_index: number, dept: DeptItem): string {
    return dept.name;
  }

  getCardGradient(_dept: DeptItem): string {
    // Same chrome as department shells — driven by global data-app-theme.
    return 'var(--app-navbar-gradient)';
  }

  /** Icons use the theme primary on a light chip (department-tile look). */
  getCardIconGlyphColor(): string {
    return 'var(--app-primary)';
  }

  /** Toolbar / hero accent color from global theme */
  getIconColor(): string {
    return 'var(--app-primary)';
  }

  togglePaletteBar(): void {
    // Kept for template safety; palette UI is retired in favour of navbar themes.
    this.paletteBarOpen = false;
    this.cdr.markForCheck();
  }

  selectPalette(_option: { id: string; gradient: string }): void {
    this.paletteBarOpen = false;
    this.cdr.markForCheck();
  }

  private justDragged = false;

  routeToDept(deptName: string): void {
    if (this.justDragged) {
      this.justDragged = false;
      return;
    }
    if (deptName === 'E Logs') {
      this.router.navigate(['/equipmentusage']);
      return;
    }

    // Same gate as card visibility (Cyclone): login-dept / full launcher / rights.
    // The old allValid + rightsRowAllowsDeptAccess loop denied QC even when the tile was shown.
    if (!this.canAccessDepartment(deptName)) {
      alertify.error('Access Denied');
      return;
    }
    this.allValid = true;

    const navMap: Record<string, string> = {
      'Plant Head': '/plant_head',
      'master': '/master',
      'Exports': '/Exports',
      'Management': '/management',
      'Admin': '/admin',
      'Account': '/accounts',
      'Marketing': '/marketing',
      'Purchase': '/purchase',
      'Human Resource': '/hr',
      'Regulatory': '/regulatory',
      'Security': '/security',
      'Planning': '/planning',
      'Production': '/fproduction',
      'Packing': '/packing',
      'Quality Control': '/qc',
      'Quality Assurance': '/qa',
      'Engineering': '/engineering',
      'IT': '/it',
      'EHS': '/ehs',
      'Store': '/store',
      'Enginering Store': '/engi-store',
      'Day Store': '/daystore',
      'Dispatch': '/dispatch',
      'R AND D': '/rnd',
      'NPD': '/npd',
      'Reception': '/reception',
      'Q-Head': '/qhead',
    };
 
    const path = navMap[deptName];
    if (path) {
      if (deptName === 'master') {
        const plant_id = localStorage.getItem('plant_id');
           this.router.navigate(['/master']);
      } else if (deptName === 'Day Store') {
        this.setDept('Store');
        this.router.navigate([path]);
      } else {
        if (deptName !== 'Plant Head' && deptName !== 'master') this.setDept(deptName);
        this.router.navigate([path]);
      }
    } else {
      alertify.error('Please Contact Admin..');
    }
    this.allValid = false;
  }

  setDept(dept: string): void {
    localStorage.setItem('department', dept);
  }
}
