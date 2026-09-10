import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

interface DashCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

const G = {
  indigo: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
  blue: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
  green: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
  pink: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
  teal: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
  mint: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
  rose: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
  sky: 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)',
  coral: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)',
  violet: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)',
  lavender: 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Landed Department';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;
  mobileSidebarOpen = false;

  private readonly sidebarBreakpointPx = 1024;
  private readonly paletteKey = 'exports_landed_palette';

  managementCards: DashCard[] = [
    { id: 'importdetails', title: 'Import Details', description: 'Import details', route: 'importdetails', icon: 'fa-file-import', category: 'Landed', gradient: G.indigo },
    { id: 'int-logistics', title: 'International Logistics', description: 'International logistics', route: 'int-logistics', icon: 'fa-globe-americas', category: 'Landed', gradient: G.blue },
    { id: 'customsimport', title: 'Customs & Import Duties', description: 'Customs and import duties', route: 'customsimport', icon: 'fa-passport', category: 'Landed', gradient: G.green },
    { id: 'cif', title: 'CIF', description: 'Cost, insurance and freight', route: 'cif', icon: 'fa-ship', category: 'Landed', gradient: G.pink },
    { id: 'dutypayment', title: 'Duty Payment', description: 'Duty payment', route: 'dutypayment', icon: 'fa-money-bill-wave', category: 'Landed', gradient: G.teal },
    { id: 'cfs', title: 'CFS', description: 'Container freight station', route: 'cfs', icon: 'fa-warehouse', category: 'Landed', gradient: G.mint },
    { id: 'freightforwarder', title: 'Freight Forwarder', description: 'Freight forwarder', route: 'freightforwarder', icon: 'fa-truck', category: 'Landed', gradient: G.rose },
    { id: 'destination-clearing', title: 'Destination Clearing', description: 'Destination clearing', route: 'destination-clearing', icon: 'fa-building', category: 'Landed', gradient: G.sky },
    { id: 'transport', title: 'Transport', description: 'Transport', route: 'transport', icon: 'fa-shipping-fast', category: 'Landed', gradient: G.coral },
    { id: 'paymentbank', title: 'Payment Bank', description: 'Payment bank', route: 'paymentbank', icon: 'fa-university', category: 'Landed', gradient: G.violet },
    { id: 'warehousing', title: 'Warehousing', description: 'Warehousing', route: 'warehousing', icon: 'fa-boxes', category: 'Landed', gradient: G.lavender },
    { id: 'inhand', title: 'Inhand', description: 'Inhand', route: 'inhand', icon: 'fa-inbox', category: 'Landed', gradient: G.indigo },
    { id: 'final', title: 'Final', description: 'Final', route: 'final', icon: 'fa-file-alt', category: 'Landed', gradient: G.blue },
    { id: 'log', title: 'Log', description: 'Log', route: 'log', icon: 'fa-list-alt', category: 'Landed', gradient: G.green },
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
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) {}

  ngOnInit(): void {
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

  get categories(): string[] {
    return ['All', ...Array.from(new Set(this.managementCards.map((c) => c.category)))];
  }

  get categoriesWithoutAll(): string[] {
    return this.categories.filter((c) => c !== 'All');
  }

  countForCategory(cat: string): number {
    return this.managementCards.filter((c) => c.category === cat).length;
  }

  getFilteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch =
        !q ||
        c.title.toLowerCase().includes(q) ||
        c.category.toLowerCase().includes(q) ||
        (c.description && c.description.toLowerCase().includes(q));
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
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

  toggleMobileSidebar(): void {
    this.mobileSidebarOpen = !this.mobileSidebarOpen;
  }

  closeMobileSidebar(): void {
    this.mobileSidebarOpen = false;
  }

  onCloseDashboard(): void {
    this.deptNav.goBack(this.route, '/Exports');
  }
}
