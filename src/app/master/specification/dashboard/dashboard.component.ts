import { Component, OnInit } from '@angular/core';

interface SpecCard {
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
};

@Component({
  selector: 'app-master-specification-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  dashboardTitle = 'Specifications (Raw & Pack)';
  sectionLabel = 'Specifications Section';
  searchQuery = '';
  selectedCategory = 'All';

  readonly cards: SpecCard[] = [
    {
      id: 'raw-spec',
      title: 'RM/PM/FP/IN Specifications',
      description: 'Raw specification module and logs',
      route: '/master/specification/raw',
      icon: 'fa-flask',
      category: 'Spec',
      gradient: G.blue,
    },
    {
      id: 'water-spec',
      title: 'Water Specification',
      description: 'Water specification module',
      route: '/master/specification/water',
      icon: 'fa-tint',
      category: 'Spec',
      gradient: G.teal,
    },
    {
      id: 'spec-revision',
      title: 'Specification Revision',
      description: 'Revision status and history',
      route: '/master/specification/specification-revision',
      icon: 'fa-history',
      category: 'Spec',
      gradient: G.indigo,
    },
    {
      id: 'obsolete-spec',
      title: 'Obsolete Specifications',
      description: 'Previous version log after approved change control',
      route: '/master/specification/obsolete-specifications',
      icon: 'fa-archive',
      category: 'Spec',
      gradient: G.amber,
    },
  ];

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;

  getCategories(): string[] {
    return Array.from(new Set(this.cards.map((c) => c.category)));
  }

  countForCategory(category: string): number {
    return this.cards.filter((c) => c.category === category).length;
  }

  get filteredCards(): SpecCard[] {
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

  trackByCardId(_index: number, card: SpecCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('master_specification_palette', palette.name);
    } catch {
      /* ignore */
    }
  }

  ngOnInit(): void {
    const saved = localStorage.getItem('master_specification_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
      if (found) {
        this.selectedPalette = found;
      }
    }
  }
}
