import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';
import { DataAccessService } from 'src/app/data-access.service';

export interface AdminHubCategory {
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
  showWhen?: string;
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
  pageTitle = 'Hygiene Management';
  sidebarTitle = 'Hygiene Modules';
  sectionLabel = 'Hygiene Modules';
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;

  isuser = 'No';
  isapprover = 'No';
  dept_head = 'No';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'admin_hygin_dashboard_category';
  private readonly paletteKey = 'admin_hygin_dashboard_palette';

  readonly categories: AdminHubCategory[] = [
    { id: 'hygiene', name: 'Hygiene', icon: 'fa-hand-sparkles' },
    { id: 'reports', name: 'Reports', icon: 'fa-file-alt' }
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'personal-hygine',
      title: 'Floor Dust Bins Cleaning',
      description: 'Floor and dustbin cleaning records',
      route: '/admin/hygin/personal-hygine',
      icon: 'fa-trash-alt',
      category: 'Hygiene',
      gradient: G.indigo,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'factory-hygiene',
      title: 'Factory Hygiene Report',
      description: 'Factory hygiene reports',
      route: '/admin/hygin/factory-hygiene',
      icon: 'fa-building',
      category: 'Hygiene',
      gradient: G.blue,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'toilet-hygiene',
      title: 'Toilet Cleaning Report',
      description: 'Toilet cleaning report',
      route: '/admin/hygin/toilet-hygiene',
      icon: 'fa-toilet',
      category: 'Hygiene',
      gradient: G.teal,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'etp',
      title: 'ETP Log Sheet',
      description: 'ETP log sheet records',
      route: '/admin/hygin/etp',
      icon: 'fa-tint',
      category: 'Hygiene',
      gradient: G.amber,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'ro-hygiene',
      title: 'RO Log Sheet',
      description: 'RO log sheet records',
      route: '/admin/hygin/ro-hygiene',
      icon: 'fa-tint',
      category: 'Hygiene',
      gradient: G.steel,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'scrap',
      title: 'Scrap Management Record',
      description: 'Scrap management under hygiene',
      route: '/admin/hygin/scrap',
      icon: 'fa-recycle',
      category: 'Hygiene',
      gradient: G.navy,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'pest',
      title: 'Pest Control Service Record',
      description: 'Pest control service records',
      route: '/admin/hygin/pest',
      icon: 'fa-bug',
      category: 'Hygiene',
      gradient: G.copper,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'personalhy',
      title: 'Personal Hygiene Report',
      description: 'Personal hygiene report',
      route: '/admin/hygin/personalhy',
      icon: 'fa-user',
      category: 'Hygiene',
      gradient: G.indigo,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'wastedisp',
      title: 'Waste Disposal Report',
      description: 'Waste disposal report',
      route: '/admin/hygin/wastedisp',
      icon: 'fa-trash',
      category: 'Hygiene',
      gradient: G.blue,
      categoryId: 'hygiene',
      showWhen: 'isuser',
    },
    {
      id: 'hygiene-reports',
      title: 'Hygiene Reports',
      description: 'View hygiene reports and logs',
      route: '/admin/hygin/hygiene-reports',
      icon: 'fa-file-alt',
      category: 'Reports',
      gradient: G.teal,
      categoryId: 'reports',
    }
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

  get currentCategory(): AdminHubCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private visibleCards(): DashCard[] {
    return this.managementCards.filter((card) => {
      if (card.showWhen === 'isuser' && this.isuser !== 'Yes') {
        return false;
      }
      if (card.showWhen === 'isapprover' && this.isapprover !== 'Yes') {
        return false;
      }
      return true;
    });
  }

  countForCategory(catId: string): number {
    const cards = this.visibleCards();
    if (catId === 'all') {
      return cards.length;
    }
    return cards.filter((c) => c.categoryId === catId).length;
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
    return this.visibleCards().filter((c) => {
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
      console.error('Admin hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: AdminHubCategory): string {
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
    this.deptNav.goBack(this.route, '/admin');
  }
}
