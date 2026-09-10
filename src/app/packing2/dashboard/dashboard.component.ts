import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface PackingCategory {
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
  showWhen?: 'plant_not_67_86' | 'plant_67_86' | 'reconciliation';
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
  isuser = 'No';
  dept_head = 'No';
  plant_type = '';
  plant_id = '';
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'packing_dashboard_category';
  private readonly paletteKey = 'packing2_dashboard_palette';

  readonly categories: PackingCategory[] = [
    { id: 'planning', name: 'Planning', icon: 'fa-clipboard-list' },
    { id: 'batch', name: 'Batch', icon: 'fa-box' },
    { id: 'transfer', name: 'Transfer', icon: 'fa-truck' },
  ];

  readonly managementCards: DashCard[] = [
    { id: 'transform', title: 'Transform From Prod', description: 'Transform from production', route: '/packing/Promproduction', icon: 'fa-database', category: 'Planning', gradient: G.teal, categoryId: 'planning' },
    { id: 'packing-plan', title: 'Packing Plan', description: 'Packing plan', route: '/packing/planning', icon: 'fa-clipboard-list', category: 'Planning', gradient: G.amber, categoryId: 'planning', showWhen: 'plant_not_67_86' },
    { id: 'packing-plan-sp', title: 'Packing Plan', description: 'Packing plan (SP approval)', route: '/packing/spplanning-approval', icon: 'fa-clipboard-list', category: 'Planning', gradient: G.amber, categoryId: 'planning', showWhen: 'plant_67_86' },
    { id: 'dispensing', title: 'Dispensing', description: 'Dispensing', route: '/packing/dispensing', icon: 'fa-prescription-bottle', category: 'Batch', gradient: G.indigo, categoryId: 'batch' },
    { id: 'bpr', title: 'Batch Pack Rec (eBPR)', description: 'Batch packing record eBPR', route: '/packing/bpr', icon: 'fa-book-medical', category: 'Batch', gradient: G.blue, categoryId: 'batch' },
    { id: 'sampling', title: 'FG Sampling', description: 'Finished goods sampling', route: '/packing/sampling', icon: 'fa-vial', category: 'Batch', gradient: G.navy, categoryId: 'batch' },
    { id: 'transfer', title: 'Transfer to FG Store', description: 'Transfer to FG store', route: '/packing/transfer', icon: 'fa-truck', category: 'Transfer', gradient: G.steel, categoryId: 'transfer' },
    { id: 'reconciliation', title: 'Pack Material Reconc', description: 'Packing material reconciliation', route: '/packing/reconciliation', icon: 'fa-balance-scale', category: 'Transfer', gradient: G.copper, categoryId: 'transfer', showWhen: 'reconciliation' },
    { id: 'packing-list', title: 'Packing List', description: 'Packing list', route: '/packing/packing-list', icon: 'fa-box-open', category: 'Transfer', gradient: G.slate, categoryId: 'transfer' },
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
    this.plant_type = this.service.getPlantConfigFields('plant_type') || '';
    this.plant_id = this.service.getPlantConfigFields('plant_id') || '';
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
          encodeURIComponent(this.loggedInDept || 'Packing')
      )
      .subscribe((response: any) => {
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.isuser = r.isuser || 'No';
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

  get currentCategory(): PackingCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    if (card.showWhen === 'plant_not_67_86' && (this.plant_id === '67' || this.plant_id === '86')) {
      return false;
    }
    if (card.showWhen === 'plant_67_86' && this.plant_id !== '67' && this.plant_id !== '86') {
      return false;
    }
    if (card.showWhen === 'reconciliation' && (this.plant_type === 'API/ Excipients' || this.isuser !== 'Yes')) {
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
      console.error('Packing hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: PackingCategory): string {
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
