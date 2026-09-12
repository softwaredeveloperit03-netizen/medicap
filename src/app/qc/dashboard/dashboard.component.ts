import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface QcCategory {
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
  software_type = '';
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;
  loggedInDept: string | null = null;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'qc_dashboard_category';
  private readonly paletteKey = 'qc_dashboard_palette';

  readonly categories: QcCategory[] = [
    { id: 'lab', name: 'Lab', icon: 'fa-flask' },
    { id: 'standards-reagents', name: 'Standards & Reagents', icon: 'fa-weight' },
    { id: 'equipment', name: 'Equipment', icon: 'fa-tools' },
    { id: 'qms', name: 'QMS', icon: 'fa-shield-alt' },
    { id: 'material-approval', name: 'Material Approval', icon: 'fa-clipboard-check' },
  ];

  readonly managementCards: DashCard[] = [
    {
      id: 'specifications',
      title: 'Specifications',
      description: 'Raw & pack material specs',
      route: '/master/specification/raw',
      icon: 'fa-cogs',
      category: 'Lab',
      gradient: G.indigo,
      categoryId: 'lab',
    },
    {
      id: 'sampling',
      title: 'Sampling',
      description: 'Sample collection & logging',
      route: '/qc/sampling',
      icon: 'fa-vial',
      category: 'Lab',
      gradient: G.blue,
      categoryId: 'lab',
    },
    {
      id: 'testing-rds',
      title: 'Testing',
      description: 'RDS testing & reports',
      route: '/qc/testing-rds',
      icon: 'fa-flask',
      category: 'Lab',
      gradient: G.teal,
      categoryId: 'lab',
    },
    {
      id: 'water',
      title: 'Water Analysis',
      description: 'Water quality testing',
      route: '/qc/water',
      icon: 'fa-tint',
      category: 'Lab',
      gradient: G.amber,
      categoryId: 'lab',
    },
    {
      id: 'controlsample',
      title: 'Control Sample',
      description: 'Control sample management',
      route: '/qc/controlsample',
      icon: 'fa-vial',
      category: 'Lab',
      gradient: G.navy,
      categoryId: 'lab',
    },
    {
      id: 'retest',
      title: 'Retest',
      description: 'Retest requests & logs',
      route: '/qc/retest',
      icon: 'fa-sync-alt',
      category: 'Lab',
      gradient: G.copper,
      categoryId: 'lab',
    },
    {
      id: 'standard',
      title: 'Standard',
      description: 'Reference standards',
      route: '/qc/standard',
      icon: 'fa-weight',
      category: 'Standards & Reagents',
      gradient: G.blue,
      categoryId: 'standards-reagents',
    },
    {
      id: 'reagents',
      title: 'Reagents',
      description: 'Reagent preparation & use',
      route: '/qc/reagents',
      icon: 'fa-vial',
      category: 'Standards & Reagents',
      gradient: G.teal,
      categoryId: 'standards-reagents',
    },
    {
      id: 'volumetric',
      title: 'Volumetric',
      description: 'Volumetric solution management',
      route: '/qc/volumetric',
      icon: 'fa-flask',
      category: 'Standards & Reagents',
      gradient: G.amber,
      categoryId: 'standards-reagents',
    },
    {
      id: 'equipments',
      title: 'Equipments',
      description: 'Equipment list & status',
      route: '/qc/equipments',
      icon: 'fa-tools',
      category: 'Equipment',
      gradient: G.steel,
      categoryId: 'equipment',
    },
    {
      id: 'calibration',
      title: 'Calibration',
      description: 'Calibration schedule & records',
      route: '/qc/calibration',
      icon: 'fa-tachometer-alt',
      category: 'Equipment',
      gradient: G.navy,
      categoryId: 'equipment',
    },
    {
      id: 'hplc',
      title: 'HPLC Management',
      description: 'Column · solutions · analysis · SST · calibration',
      route: '/qc/hpl/hub',
      icon: 'fa-chart-line',
      category: 'Equipment',
      gradient: G.slate,
      categoryId: 'equipment',
    },
    {
      id: 'cleaning',
      title: 'Cleaning',
      description: 'Cleaning validation',
      route: '/qc/cleaning',
      icon: 'fa-broom',
      category: 'QMS',
      gradient: G.indigo,
      categoryId: 'qms',
    },
    {
      id: 'cleaning-validation',
      title: 'Cleaning Validation and Routine Cleaning of Equipment',
      description: 'SOP-QA-002 · sample collection · approval before production',
      route: '/qc/cleaning-validation',
      icon: 'fa-clipboard-check',
      category: 'QMS',
      gradient: G.steel,
      categoryId: 'qms',
    },
    {
      id: 'oos',
      title: 'OOS',
      description: 'Out of specification logs',
      route: '/qc/oos',
      icon: 'fa-times-circle',
      category: 'QMS',
      gradient: G.blue,
      categoryId: 'qms',
    },
    {
      id: 'audit-trail',
      title: 'Audit Trail QC',
      description: '21 CFR Part 11 electronic audit trail · interpretation PDF · archive',
      route: '/qc/audit-trail',
      icon: 'fa-clipboard-list',
      category: 'QMS',
      gradient: G.teal,
      categoryId: 'qms',
    },
    {
      id: 'client-doc-request',
      title: 'Client Document Request',
      description: 'Upload documents assigned from Marketing via QA Head',
      route: '/qc/client-doc-request',
      icon: 'fa-file-alt',
      category: 'QMS',
      gradient: G.copper,
      categoryId: 'qms',
    },
    {
      id: 'temperature',
      title: 'Temp. & Humidity Rec',
      description: 'Temperature and humidity records',
      route: '/qc/temperature',
      icon: 'fa-thermometer-half',
      category: 'Equipment',
      gradient: G.amber,
      categoryId: 'equipment',
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
    this.software_type = this.service.getPlantConfigFields('software_type') || '';
  }

  ngOnInit(): void {
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

  get isNarrowViewport(): boolean {
    return this.windowWidth() < this.sidebarBreakpointPx;
  }

  get currentCategory(): QcCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  private isCardVisible(card: DashCard): boolean {
    return !(card.id === 'water' && this.software_type === 'Nutraceutical');
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
      console.error('QC hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: QcCategory): string {
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
