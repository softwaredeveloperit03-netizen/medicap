import { Component, OnInit } from '@angular/core';
import { HPLC_DEMO_MODE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';
import { HPLC_COMPLIANCE } from '../shared/hplc-workflow.config';

interface HplcHubCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

const G = {
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
  navy: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
};

@Component({
  selector: 'app-hplc-hub-dashboard',
  templateUrl: './hub-dashboard.component.html',
  styleUrls: ['./hub-dashboard.component.css'],
})
export class HplcHubDashboardComponent implements OnInit {
  dashboardTitle = 'HPLC Management System';
  hubTagline =
    'Column lifecycle - Multi-instrument solutions - Analysis - ICH chromatography - SST - Calibration - Audit';
  complianceRefs = HPLC_COMPLIANCE;
  sectionLabel = 'HPLC Modules';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;
  demoMode = HPLC_DEMO_MODE;
  kpis: any;

  readonly allCards: HplcHubCard[] = [
    {
      id: 'column-master',
      title: 'Column Master',
      description: 'USP L-type standard columns',
      route: '/qc/hpl/column/master',
      icon: 'fa-database',
      category: 'Column',
      gradient: G.blue,
    },
    {
      id: 'column-ordering',
      title: 'Column Ordering',
      description: 'PO & vendor orders',
      route: '/qc/hpl/column/ordering',
      icon: 'fa-shopping-cart',
      category: 'Column',
      gradient: G.teal,
    },
    {
      id: 'column-receiving',
      title: 'Column Receiving',
      description: 'Inward & COA check',
      route: '/qc/hpl/column/receiving',
      icon: 'fa-truck-loading',
      category: 'Column',
      gradient: G.indigo,
    },
    {
      id: 'column-regeneration',
      title: 'Column Regeneration',
      description: 'Process + metrics matrix',
      route: '/qc/hpl/column/regeneration',
      icon: 'fa-sync-alt',
      category: 'Column',
      gradient: G.amber,
    },
    {
      id: 'column-logbook',
      title: 'Column Logbook',
      description: 'Usage & injection count',
      route: '/qc/hpl/column/logbook',
      icon: 'fa-book',
      category: 'Column',
      gradient: G.navy,
    },
    {
      id: 'column-destruction',
      title: 'Column Destruction',
      description: 'Retirement record',
      route: '/qc/hpl/column/destruction',
      icon: 'fa-trash-alt',
      category: 'Column',
      gradient: G.steel,
    },
    {
      id: 'mobile-phase',
      title: 'Mobile Phase',
      description: 'Prep per HPLC unit',
      route: '/qc/hpl/solution/mobile-phase',
      icon: 'fa-vial',
      category: 'Solution',
      gradient: G.blue,
    },
    {
      id: 'diluent',
      title: 'Diluent',
      description: 'Diluent preparation',
      route: '/qc/hpl/solution/diluent',
      icon: 'fa-tint',
      category: 'Solution',
      gradient: G.teal,
    },
    {
      id: 'stock',
      title: 'Stock Solutions',
      description: 'RS / STD stocks',
      route: '/qc/hpl/solution/stock',
      icon: 'fa-flask',
      category: 'Solution',
      gradient: G.indigo,
    },
    {
      id: 'dilution',
      title: 'Dilution',
      description: 'Serial dilution chain',
      route: '/qc/hpl/solution/dilution',
      icon: 'fa-percentage',
      category: 'Solution',
      gradient: G.amber,
    },
    {
      id: 'issuance',
      title: 'Issuance',
      description: 'Issue to analysis',
      route: '/qc/hpl/solution/issuance',
      icon: 'fa-share-square',
      category: 'Solution',
      gradient: G.navy,
    },
    {
      id: 'solution-log',
      title: 'Solution Log',
      description: 'Full preparation log',
      route: '/qc/hpl/solution/log',
      icon: 'fa-list-alt',
      category: 'Solution',
      gradient: G.steel,
    },
    {
      id: 'analysis',
      title: 'HPLC Analysis',
      description: 'Worksheet + calculations',
      route: '/qc/hpl/analysis',
      icon: 'fa-chart-line',
      category: 'Analysis',
      gradient: G.blue,
    },
    {
      id: 'interpretation',
      title: 'Chromatogram Interpretation',
      description: 'ICH / USP peak ID',
      route: '/qc/hpl/interpretation',
      icon: 'fa-search-plus',
      category: 'Analysis',
      gradient: G.teal,
    },
    {
      id: 'suitability',
      title: 'System Suitability',
      description: 'SST logs & limits',
      route: '/qc/hpl/suitability',
      icon: 'fa-check-double',
      category: 'Analysis',
      gradient: G.indigo,
    },
    {
      id: 'calibration',
      title: 'HPLC Calibration',
      description: 'Pump, λ, injector, detector',
      route: '/qc/hpl/calibration',
      icon: 'fa-ruler-combined',
      category: 'Analysis',
      gradient: G.amber,
    },
    {
      id: 'audit',
      title: 'Audit Trail',
      description: 'Immutable activity log',
      route: '/qc/hpl/audit',
      icon: 'fa-history',
      category: 'Analysis',
      gradient: G.navy,
    },
  ];

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
  ];
  selectedPalette = this.paletteOptions[0];

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.kpis = this.demo.getKpis();
    const saved = localStorage.getItem('qc_hplc_hub_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
      if (found) {
        this.selectedPalette = found;
      }
    }
  }

  getCategories(): string[] {
    return Array.from(new Set(this.allCards.map((c) => c.category)));
  }

  countForCategory(category: string): number {
    return this.allCards.filter((c) => c.category === category).length;
  }

  categoryIcon(category: string): string {
    if (category === 'Column') {
      return 'fa-columns';
    }
    if (category === 'Solution') {
      return 'fa-flask';
    }
    if (category === 'Analysis') {
      return 'fa-chart-line';
    }
    return 'fa-vial';
  }

  get filteredCards(): HplcHubCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.allCards.filter((x) => {
      const matchCategory = this.selectedCategory === 'All' || x.category === this.selectedCategory;
      const matchSearch =
        !q ||
        x.title.toLowerCase().includes(q) ||
        x.description.toLowerCase().includes(q) ||
        x.category.toLowerCase().includes(q);
      return matchCategory && matchSearch;
    });
  }

  getSectionLabel(): string {
    if (this.selectedCategory === 'All') {
      return this.sectionLabel;
    }
    return this.selectedCategory;
  }

  trackByCardId(_index: number, card: HplcHubCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('qc_hplc_hub_palette', palette.name);
    } catch {
      /* ignore */
    }
  }
}
