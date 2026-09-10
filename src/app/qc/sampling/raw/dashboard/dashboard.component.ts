import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DeptCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

interface SidebarLink {
  id: string;
  title: string;
  route: string;
  icon: string;
  showWhen?: 'dept_head' | 'trainig_cordinator';
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  dashboardTitle = 'Sampling (Raw)';
  sectionLabel = 'Sampling Raw Section';
  closeRouterLink = '/qc/sampling';
  searchQuery = '';
  selectedCategory = 'All';
  selectedSidebarModule: 'ALL' | 'RAW_SAMPLING' = 'ALL';
  showPalette = false;
  private readonly rawSamplingIds = new Set([
    'receving',
    'allocation',
    'allocationLog',
    'line',
    'sample',
    'checking',
    'approval',
    'log',
    'rejectedSampling',
    'label',
    'sampling-room'
  ]);

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
    { name: 'Mint', swatch: '#d1fae5', background: 'linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%)', cardShadow: '0 10px 30px rgba(16,185,129,0.15)' },
  ];
  selectedPalette: { name: string; swatch: string; background: string; cardShadow: string } = this.paletteOptions[0];

  cards: DeptCard[] = [
    { id: 'receving', title: 'Sampling Receiving', description: 'RM/PM receiving for sampling', route: 'receving', icon: 'fa-truck-loading', category: 'Raw', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)' },
    { id: 'allocation', title: 'Sampling Allocation', description: 'Sampling allocation workflow', route: 'allocation', icon: 'fa-tasks', category: 'Raw', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)' },
    { id: 'allocationLog', title: 'Allocation Log', description: 'Allocation history and status', route: 'allocationLog', icon: 'fa-clipboard-list', category: 'Raw', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)' },
    { id: 'line', title: 'Sampling Line Clearance', description: 'Pre-sampling line operations', route: 'PreSampling', icon: 'fa-align-justify', category: 'Raw', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
    { id: 'sample', title: 'Sampling Activities', description: 'Raw material sample processing', route: 'sample', icon: 'fa-vial', category: 'Raw', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
    { id: 'checking', title: 'Sampling Checking', description: 'Check and verify sampling data', route: 'checking', icon: 'fa-redo-alt', category: 'Raw', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approval', title: 'Sampling Approval', description: 'Approve sampled materials', route: 'approval', icon: 'fa-check-circle', category: 'Raw', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'log', title: 'Sampling Log', description: 'Completed sampling logs', route: 'log', icon: 'fa-list', category: 'Raw', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'rejectedSampling', title: 'Rejected Sampling', description: 'Rejected sample records', route: 'rejectedSampling', icon: 'fa-times-circle', category: 'Raw', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'label', title: 'Label', description: 'Print and manage labels', route: 'label', icon: 'fa-tag', category: 'Raw', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)' },
    { id: 'sampling-room', title: 'Sampling Room Usage and Cleaning Log', description: 'Room usage & cleaning log', route: '/qc/sampling/sampling-room', icon: 'fa-broom', category: 'Raw', gradient: 'linear-gradient(135deg, #5b21b6 0%, #8b5cf6 100%)' },
  ];

  commonSidebarLinks: SidebarLink[] = [
    { id: 'calibration', title: 'Calibration', route: '/calibration', icon: 'fa-ruler-combined' },
    { id: 'training', title: 'Training', route: '/training', icon: 'fa-graduation-cap', showWhen: 'trainig_cordinator' },
    { id: 'pm-intimation', title: 'PM Intimation', route: '/preventiveimain', icon: 'fa-tools' },
    { id: 'dept-head', title: 'Dept Head', route: '/hrfordepthead', icon: 'fa-user-tie', showWhen: 'dept_head' },
    { id: 'indent', title: 'Indent', route: '/indend/raw', icon: 'fa-boxes' },
    { id: 'qms', title: 'QMS', route: '/qa/qms', icon: 'fa-clipboard-check' },
    { id: 'breakdown', title: 'Breakdown', route: '/breakdown', icon: 'fa-exclamation-triangle' }
  ];

  counts: {
    rm: number;
    pm: number;
    fg: number;
    inprocess: number;
    pending: number;
    complete: number;
    active: number;
    checked: number;
    rejected: number;
    total: number;
  } | null = null;
  inProgress: any[] = [];
  statsLoading = false;

  constructor(public service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.get_rights();
    this.loadDashboardStats();
    const savedPalette = localStorage.getItem('qc_sampling_raw_dashboard_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }

  loadDashboardStats(): void {
    this.statsLoading = true;
    this.service.get('qc/sampling.php?type=dashboardStats').subscribe({
      next: (res: any) => {
        this.statsLoading = false;
        if (res?.status === 'success') {
          this.counts = res.counts || null;
          this.inProgress = Array.isArray(res.in_progress) ? res.in_progress : [];
        } else {
          this.counts = null;
          this.inProgress = [];
        }
      },
      error: () => {
        this.statsLoading = false;
        this.counts = null;
        this.inProgress = [];
      },
    });
  }

  shortType(t: string): string {
    const v = (t || '').toLowerCase();
    if (v.includes('raw')) return 'RM';
    if (v.includes('pack')) return 'PM';
    if (v.includes('finish') || v.includes('fg') || v.includes('in-process') || v.includes('in process') || v.includes('inprocess')) {
      return 'FG';
    }
    return t || '—';
  }

  statusClass(status: string): string {
    const s = (status || '').toLowerCase().trim();
    if (s === 'approved' || s === 'complete') return 'samp-status--ok';
    if (s === 'rejected') return 'samp-status--bad';
    if (s === 'checked' || s === 'pending' || s === 'allocate') return 'samp-status--warn';
    if (s === 'active' || s === 'inprocess' || s === 'allocated') return 'samp-status--info';
    if (s.includes('cleaning')) return 'samp-status--violet';
    return 'samp-status--muted';
  }

  trackBySampleId(_i: number, row: any): number | string {
    return row?.id || row?.sampling_no || _i;
  }

  getCategories(): string[] {
    return ['All'].concat(Array.from(new Set(this.cards.map(c => c.category))));
  }

  getFilteredCards(): DeptCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter(c => {
      const matchSidebar =
        this.selectedSidebarModule === 'ALL' ||
        (this.selectedSidebarModule === 'RAW_SAMPLING' && this.rawSamplingIds.has(c.id));
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().indexOf(q) !== -1 || (c.description && c.description.toLowerCase().indexOf(q) !== -1) || c.category.toLowerCase().indexOf(q) !== -1;
      return matchSidebar && matchCategory && matchSearch;
    });
  }

  trackByCardId(_i: number, card: DeptCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('qc_sampling_raw_dashboard_palette', palette.name);
    } catch (e) {}
  }

  selectSidebarModule(module: 'ALL' | 'RAW_SAMPLING'): void {
    this.selectedSidebarModule = module;
    this.selectedCategory = 'All';
    this.searchQuery = '';
  }

  showAllTabs(): void {
    this.selectSidebarModule('ALL');
  }

  getOtherSidebarLinks(): SidebarLink[] {
    return this.commonSidebarLinks.filter(link => {
      if (link.showWhen === 'dept_head' && this.dept_head !== 'Yes') {
        return false;
      }
      if (link.showWhen === 'trainig_cordinator' && this.trainig_cordinator !== 'Yes') {
        return false;
      }
      return true;
    });
  }

  trackBySidebarLinkId(_index: number, link: SidebarLink): string {
    return link.id;
  }

  rights: any;
  dept_head = 'No';
  trainig_cordinator = 'No';
  loggedInDept: string | null = null;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + encodeURIComponent(this.loggedInDept || ''))
      .subscribe((response: any) => {
        this.rights = response;
        if (this.rights && this.rights[0]) {
          this.dept_head = this.rights[0].dept_head || 'No';
          this.trainig_cordinator = this.rights[0].trainig_cordinator || 'No';
        }
      });
  }
}
