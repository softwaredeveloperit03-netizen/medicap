import { Component, OnInit } from '@angular/core';

interface SpecRawCard {
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
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  dashboardTitle = 'RM/PM/FP/IN Specifications';
  sectionLabel = 'Specification Modules';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;

  readonly cards: SpecRawCard[] = [
    {
      id: 'new',
      title: 'New Specification',
      description: 'Create a new raw / pack specification',
      route: 'new',
      icon: 'fa-book',
      category: 'Workflow',
      gradient: G.blue,
    },
    {
      id: 'checking',
      title: 'Checking',
      description: 'Review pending specifications',
      route: 'checking',
      icon: 'fa-clipboard-check',
      category: 'Workflow',
      gradient: G.teal,
    },
    {
      id: 'approval',
      title: 'Approval',
      description: 'Approve checked specifications',
      route: 'approval',
      icon: 'fa-check-circle',
      category: 'Workflow',
      gradient: G.indigo,
    },
    {
      id: 'correction',
      title: 'Correction',
      description: 'Correct returned specifications',
      route: 'correction',
      icon: 'fa-pen-to-square',
      category: 'Workflow',
      gradient: G.amber,
    },
    {
      id: 'specification-logs',
      title: 'Specification Logs',
      description: 'Approved specification register',
      route: 'specification-logs',
      icon: 'fa-clipboard-list',
      category: 'Records',
      gradient: G.navy,
    },
    {
      id: 'rejected',
      title: 'Rejected Specification',
      description: 'Rejected or obsolete entries',
      route: 'rejected',
      icon: 'fa-times-circle',
      category: 'Records',
      gradient: G.amber,
    },
    {
      id: 'inprocess',
      title: 'Inprocess Specification',
      description: 'Specifications in progress',
      route: 'inprocess',
      icon: 'fa-spinner',
      category: 'Records',
      gradient: G.steel,
    },
  ];

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
  ];
  selectedPalette = this.paletteOptions[0];

  getCategories(): string[] {
    return Array.from(new Set(this.cards.map((c) => c.category)));
  }

  countForCategory(category: string): number {
    return this.cards.filter((c) => c.category === category).length;
  }

  categoryIcon(category: string): string {
    if (category === 'Workflow') {
      return 'fa-route';
    }
    if (category === 'Records') {
      return 'fa-clipboard-list';
    }
    return 'fa-file-contract';
  }

  get filteredCards(): SpecRawCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter((x) => {
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

  trackByCardId(_index: number, card: SpecRawCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('master_spec_raw_palette', palette.name);
    } catch {
      /* ignore */
    }
  }

  ngOnInit(): void {
    const saved = localStorage.getItem('master_spec_raw_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
      if (found) {
        this.selectedPalette = found;
      }
    }
  }
}
