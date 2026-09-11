import { Component, OnInit } from '@angular/core';

interface RevisionCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

const G = {
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
};

@Component({
  selector: 'app-specification-revision',
  templateUrl: './specification-revision.component.html',
  styleUrls: ['../dashboard/dashboard.component.css', './specification-revision.component.css'],
})
export class SpecificationRevisionComponent implements OnInit {
  dashboardTitle = 'Specification Revision';
  sectionLabel = 'Revision Modules';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;

  readonly revisionCards: RevisionCard[] = [
    {
      id: 'specification-revision',
      title: 'Specification Revision',
      description: 'Unified specification logs table',
      route: '/master/specification/raw/specification-revision/specification-revision',
      icon: 'fa-file-alt',
      category: 'Workflow',
      gradient: G.blue,
    },
    {
      id: 'revision-status',
      title: 'Revision Status',
      description: 'Track due, active and under-revision specifications',
      route: '/master/specification/specification-revision/revision-status',
      icon: 'fa-tasks',
      category: 'Workflow',
      gradient: G.teal,
    },
    {
      id: 'revision-history',
      title: 'Revision History Log',
      description: 'View revision records and history details',
      route: '/master/specification/specification-revision/revision-history-log',
      icon: 'fa-history',
      category: 'Records',
      gradient: G.indigo,
    },
    {
      id: 'periodic-review',
      title: 'Periodic Review of Specification',
      description: 'Periodic review scheduling and tracking',
      route: '/master/specification/specification-revision/periodic-review',
      icon: 'fa-calendar-check',
      category: 'Records',
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

  getCategories(): string[] {
    return Array.from(new Set(this.revisionCards.map((c) => c.category)));
  }

  countForCategory(category: string): number {
    return this.revisionCards.filter((c) => c.category === category).length;
  }

  categoryIcon(category: string): string {
    if (category === 'Workflow') {
      return 'fa-route';
    }
    return 'fa-clipboard-list';
  }

  get filteredCards(): RevisionCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.revisionCards.filter((card) => {
      const matchCategory = this.selectedCategory === 'All' || card.category === this.selectedCategory;
      const matchSearch =
        !q ||
        card.title.toLowerCase().includes(q) ||
        card.description.toLowerCase().includes(q) ||
        card.category.toLowerCase().includes(q);
      return matchCategory && matchSearch;
    });
  }

  getSectionLabel(): string {
    if (this.selectedCategory === 'All') {
      return this.sectionLabel;
    }
    return this.selectedCategory;
  }

  trackByCardId(_index: number, card: RevisionCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('master_spec_revision_palette', palette.name);
    } catch {
      /* ignore */
    }
  }

  ngOnInit(): void {
    const saved = localStorage.getItem('master_spec_revision_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
      if (found) {
        this.selectedPalette = found;
      }
    }
  }
}
