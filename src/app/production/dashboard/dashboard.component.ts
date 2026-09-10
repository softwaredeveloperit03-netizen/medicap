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
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'production_dashboard_category';
  private readonly paletteKey = 'production_dashboard_palette';

  readonly categories: ProductionCategory[] = [
    { id: 'masters', name: 'Masters', icon: 'fa-layer-group' },
    { id: 'batch', name: 'Batch', icon: 'fa-industry' },
    { id: 'reports', name: 'Reports', icon: 'fa-file-alt' },
    { id: 'qms', name: 'QMS', icon: 'fa-clipboard-check' },
  ];

  readonly managementCards: DashCard[] = [
    { id: 'process', title: 'Process Type Master', description: 'Process type master', route: '/production/process', icon: 'fa-cogs', category: 'Masters', gradient: G.indigo, categoryId: 'masters' },
    { id: 'stages-master', title: 'BMR Checklist', description: 'BMR checklist and stage master', route: '/production/stages-master', icon: 'fa-clipboard-check', category: 'Masters', gradient: G.blue, categoryId: 'masters' },
    { id: 'bom', title: 'Bill Of Materials', description: 'Bill of materials', route: '/production/bom', icon: 'fa-file-invoice', category: 'Masters', gradient: G.teal, categoryId: 'masters' },
    { id: 'ebmr', title: 'Master Formula Card', description: 'Master formula card', route: '/production/ebmr', icon: 'fa-th-large', category: 'Masters', gradient: G.steel, categoryId: 'masters' },
    { id: 'yield-master', title: 'Yield Master', description: 'Yield master', route: '/production/yield-master', icon: 'fa-th-large', category: 'Masters', gradient: G.navy, categoryId: 'masters' },
    { id: 'technical-info', title: 'Technical Info. Sheet', description: 'Technical information sheet', route: '/production/technical-info', icon: 'fa-info-circle', category: 'Masters', gradient: G.amber, categoryId: 'masters' },
    { id: 'batch-planning', title: 'Batch Planning', description: 'Batch planning', route: '/production/batch-planning', icon: 'fa-calendar-alt', category: 'Batch', gradient: G.indigo, categoryId: 'batch' },
    { id: 'bmr', title: 'Batch Manufacturing Record', description: 'Batch manufacturing record', route: '/production/bmr', icon: 'fa-industry', category: 'Batch', gradient: G.blue, categoryId: 'batch' },
    { id: 'technical', title: 'Inprocess Sample', description: 'Inprocess sample', route: '/production/technical', icon: 'fa-flask', category: 'Batch', gradient: G.teal, categoryId: 'batch' },
    { id: 'batch-yield', title: 'Yield Reconciliation', description: 'Yield reconciliation', route: '/production/batch/yield', icon: 'fa-chart-line', category: 'Batch', gradient: G.steel, categoryId: 'batch' },
    { id: 'rejection', title: 'Rejection', description: 'Rejection management', route: '/production/rejection', icon: 'fa-times-circle', category: 'Batch', gradient: G.amber, categoryId: 'batch' },
    { id: 'check-shortages', title: 'Shortages Calculation', description: 'Shortages calculation', route: '/production/check-shortages', icon: 'fa-exclamation-triangle', category: 'Batch', gradient: G.navy, categoryId: 'batch' },
    { id: 'qa-approved-plans', title: 'QA Approved Plans', description: 'QA approved plans', route: '/production/qa-approved-plans', icon: 'fa-calendar-check', category: 'Batch', gradient: G.copper, categoryId: 'batch' },
    { id: 'additional', title: 'Additional Material', description: 'Additional material', route: '/production/additional', icon: 'fa-plus', category: 'Batch', gradient: G.indigo, categoryId: 'batch' },
    { id: 'lmr', title: 'Lot Manufacturing Record', description: 'Lot manufacturing record', route: '/production/lmr', icon: 'fa-th-large', category: 'Batch', gradient: G.blue, categoryId: 'batch' },
    { id: 'recovery', title: 'Recovery Management', description: 'Recovery management', route: '/production/recovery', icon: 'fa-th-large', category: 'Batch', gradient: G.teal, categoryId: 'batch' },
    { id: 'maintenance', title: 'Maintenance', description: 'Maintenance', route: '/production/maintenance', icon: 'fa-th-large', category: 'Batch', gradient: G.steel, categoryId: 'batch' },
    { id: 'batch-completed', title: 'Production Report', description: 'Production report', route: '/production/batch/completed', icon: 'fa-file-alt', category: 'Reports', gradient: G.amber, categoryId: 'reports' },
    { id: 'logbooks', title: 'Logbooks', description: 'Production logbooks', route: '/production/logbooks', icon: 'fa-book', category: 'Reports', gradient: G.navy, categoryId: 'reports' },
    { id: 'temperature', title: 'Temp. & Humidity Rec', description: 'Temperature and humidity records', route: '/production/temperature', icon: 'fa-thermometer-half', category: 'Reports', gradient: G.amber, categoryId: 'reports' },
    { id: 'qms', title: 'QMS', description: 'Quality management system', route: '/production/qms', icon: 'fa-file-alt', category: 'QMS', gradient: G.copper, categoryId: 'qms' },
    { id: 'deviation', title: 'Deviation', description: 'Deviation management', route: '/production/deviation', icon: 'fa-th-large', category: 'QMS', gradient: G.indigo, categoryId: 'qms' },
    { id: 'changecontrol', title: 'Change Control', description: 'Change control', route: '/production/changecontrol', icon: 'fa-th-large', category: 'QMS', gradient: G.blue, categoryId: 'qms' },
    { id: 'incidents', title: 'Incident Reporting', description: 'Incident reporting', route: '/production/incidents', icon: 'fa-th-large', category: 'QMS', gradient: G.teal, categoryId: 'qms' },
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
        this.isuser = r.isuser || 'No';
        this.ischecker = r.ischecker || 'No';
        this.isapprover = r.isapprover || 'No';
        this.qms_approver = r.qms_approver || 'No';
        this.dept_head = r.dept_head || 'No';
        this.isauditor = r.isauditor || 'No';
        this.plant_head = r.plant_head || 'No';
        this.shift_allocator = r.shift_allocator || 'No';
      });
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';

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
