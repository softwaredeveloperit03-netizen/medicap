import { Component, HostListener, OnInit } from '@angular/core';
import {
  DashboardSidebarEntry,
  sidebarFromCategories,
  slugifyLabel,
} from 'src/app/shared/dashboard-master-nav';

interface StaticCard {
  id: string;
  title: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  selectedCategory = 'All';
  searchQuery = '';
  showPalette = false;
  mobileSidebarOpen = false;
  private readonly sidebarBreakpointPx = 1024;

  readonly cards: StaticCard[] = [
    {
      id: 'approval-licences',
      title: 'Approval / Licences',
      route: 'approvalLicences',
      icon: 'fa-tachometer-alt',
      category: 'Regulatory',
      gradient: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
    },
  ];

  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    {
      name: 'Corporate',
      background: 'linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%)',
      cardShadow: '0 8px 24px rgba(15, 23, 42, 0.08)',
    },
    { name: 'Light', background: '#f8fafc', cardShadow: '0 8px 24px rgba(15, 23, 42, 0.06)' },
    {
      name: 'Cool gray',
      background: 'linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%)',
      cardShadow: '0 8px 24px rgba(51, 65, 85, 0.1)',
    },
    {
      name: 'Warm white',
      background: 'linear-gradient(180deg, #fffdfb 0%, #f8fafc 100%)',
      cardShadow: '0 8px 24px rgba(180, 83, 9, 0.08)',
    },
  ];
  selectedPalette = this.paletteOptions[0];

  ngOnInit(): void {
    const saved = localStorage.getItem('regulatory_dashboard_palette');
    if (saved) {
      const found = this.paletteOptions.find((p) => p.name === saved);
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

  toggleMobileSidebar(): void {
    this.mobileSidebarOpen = !this.mobileSidebarOpen;
  }

  closeMobileSidebar(): void {
    this.mobileSidebarOpen = false;
  }

  getCategories(): string[] {
    return ['All', ...Array.from(new Set(this.cards.map((c) => c.category)))];
  }

  get sidebarEntries(): DashboardSidebarEntry[] {
    return sidebarFromCategories(this.getCategories(), (label) =>
      label === 'All' ? 'fa-layer-group' : 'fa-folder-open'
    );
  }

  get selectedSidebarId(): string {
    return this.selectedCategory === 'All' ? 'all' : slugifyLabel(this.selectedCategory);
  }

  get currentSidebarEntry(): DashboardSidebarEntry | undefined {
    return this.sidebarEntries.find((e) => e.id === this.selectedSidebarId);
  }

  selectSidebar(entry: DashboardSidebarEntry): void {
    this.selectedCategory = entry.filterKey;
    if (this.isNarrowViewport) {
      this.mobileSidebarOpen = false;
    }
  }

  countForSidebar(filterKey: string): number {
    return this.cards.filter((c) => {
      if (filterKey === 'All') {
        return true;
      }
      return c.category === filterKey;
    }).length;
  }

  trackBySidebarId(_index: number, entry: DashboardSidebarEntry): string {
    return entry.id;
  }

  get filteredCards(): StaticCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter((c) => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch =
        !q ||
        c.title.toLowerCase().includes(q) ||
        c.category.toLowerCase().includes(q) ||
        c.route.toLowerCase().includes(q);
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_index: number, card: StaticCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('regulatory_dashboard_palette', palette.name);
    } catch {
      /* ignore */
    }
  }
}
