import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

/** Sidebar categories — each card belongs to exactly one group */
export interface EhsCategory {
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
  dept_head = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'ehs_dashboard_category';
  private readonly paletteKey = 'ehs_dashboard_palette';

  readonly categories: EhsCategory[] = [
    { id: 'permits', name: 'Permits', icon: 'fa-id-card' },
    { id: 'monitoring', name: 'Monitoring', icon: 'fa-chart-line' },
    { id: 'incidents', name: 'Incidents', icon: 'fa-exclamation-triangle' },
    { id: 'people', name: 'People', icon: 'fa-users' },
    { id: 'qms', name: 'QMS', icon: 'fa-clipboard-check' },
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'entry-permit',
      title: 'Entry Permit',
      description: 'Work area entry permits',
      route: '/ehs/entry-permit',
      icon: 'fa-id-card',
      category: 'Permits',
      gradient: G.indigo,
      categoryId: 'permits',
    },
    {
      id: 'hot-work',
      title: 'Hot Work',
      description: 'Hot work permits',
      route: '/ehs/hot-work',
      icon: 'fa-fire',
      category: 'Permits',
      gradient: G.amber,
      categoryId: 'permits',
    },
    {
      id: 'excavation-work',
      title: 'Excavation Work',
      description: 'Excavation work permits',
      route: '/ehs/excavation-work',
      icon: 'fa-shovel',
      category: 'Permits',
      gradient: G.copper,
      categoryId: 'permits',
    },
    {
      id: 'work-height',
      title: 'Work at Height',
      description: 'Work at height permits',
      route: '/ehs/work-height',
      icon: 'fa-mountain',
      category: 'Permits',
      gradient: G.steel,
      categoryId: 'permits',
    },
    {
      id: 'inprocess',
      title: 'Inprocess',
      description: 'In-process monitoring',
      route: '/ehs/inprocess',
      icon: 'fa-flask',
      category: 'Monitoring',
      gradient: G.blue,
      categoryId: 'monitoring',
    },
    {
      id: 'firehydrant',
      title: 'Fire Hydrant',
      description: 'Fire hydrant inspection',
      route: '/ehs/firehydrant',
      icon: 'fa-fire-extinguisher',
      category: 'Monitoring',
      gradient: G.teal,
      categoryId: 'monitoring',
    },
    {
      id: 'firstaidbox',
      title: 'First Aid Box',
      description: 'First aid box checks',
      route: '/ehs/firstaidbox',
      icon: 'fa-first-aid',
      category: 'Monitoring',
      gradient: G.navy,
      categoryId: 'monitoring',
    },
    {
      id: 'elecweighbal',
      title: 'Weighing Balance',
      description: 'Electronic weighing balance',
      route: '/ehs/elecweighbal',
      icon: 'fa-weight',
      category: 'Monitoring',
      gradient: G.slate,
      categoryId: 'monitoring',
    },
    {
      id: 'Inspfireextinguisher',
      title: 'Fire Extinguisher Insp',
      description: 'Fire extinguisher inspection',
      route: '/ehs/Inspfireextinguisher',
      icon: 'fa-fire-alt',
      category: 'Monitoring',
      gradient: G.indigo,
      categoryId: 'monitoring',
    },
    {
      id: 'phmeter',
      title: 'pH Meter',
      description: 'pH meter calibration and checks',
      route: '/ehs/phmeter',
      icon: 'fa-vial',
      category: 'Monitoring',
      gradient: G.amber,
      categoryId: 'monitoring',
    },
    {
      id: 'optdsmeter',
      title: 'OTDS Meter',
      description: 'OTDS meter records',
      route: '/ehs/optdsmeter',
      icon: 'fa-tachometer-alt',
      category: 'Monitoring',
      gradient: G.steel,
      categoryId: 'monitoring',
    },
    {
      id: 'Accidentreport',
      title: 'Accident Report',
      description: 'Workplace accident reporting',
      route: '/ehs/Accidentreport',
      icon: 'fa-exclamation-triangle',
      category: 'Incidents',
      gradient: G.copper,
      categoryId: 'incidents',
    },
    {
      id: 'biomedwaste',
      title: 'Biomedical Waste',
      description: 'Biomedical waste management',
      route: '/ehs/biomedwaste',
      icon: 'fa-biohazard',
      category: 'Incidents',
      gradient: G.blue,
      categoryId: 'incidents',
    },
    {
      id: 'mocdrill',
      title: 'MOC Drill',
      description: 'Management of change drills',
      route: '/ehs/mocdrill',
      icon: 'fa-hard-hat',
      category: 'Incidents',
      gradient: G.teal,
      categoryId: 'incidents',
    },
    {
      id: 'toolboxattend',
      title: 'Toolbox Attendance',
      description: 'Toolbox talk attendance',
      route: '/ehs/toolboxattend',
      icon: 'fa-toolbox',
      category: 'People',
      gradient: G.navy,
      categoryId: 'people',
    },
    {
      id: 'Attendence',
      title: 'EHS Attendance',
      description: 'EHS staff attendance',
      route: '/ehs/Attendence',
      icon: 'fa-user-check',
      category: 'People',
      gradient: G.slate,
      categoryId: 'people',
    },
    {
      id: 'Committee',
      title: 'Safety Committee',
      description: 'Safety committee meetings',
      route: '/ehs/Committee',
      icon: 'fa-users',
      category: 'People',
      gradient: G.indigo,
      categoryId: 'people',
    },
    {
      id: 'training',
      title: 'Training',
      description: 'EHS training records',
      route: '/ehs/training',
      icon: 'fa-graduation-cap',
      category: 'People',
      gradient: G.amber,
      categoryId: 'people',
    },
    {
      id: 'qms',
      title: 'QMS',
      description: 'EHS quality management',
      route: '/ehs/qms',
      icon: 'fa-clipboard-check',
      category: 'QMS',
      gradient: G.steel,
      categoryId: 'qms',
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

  get currentCategory(): EhsCategory | undefined {
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
      console.error('EHS hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: EhsCategory): string {
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
