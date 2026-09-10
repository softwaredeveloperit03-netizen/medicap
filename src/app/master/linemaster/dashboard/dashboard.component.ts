import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DashCard {
  id: string;
  title: string;
  description: string;
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
  pageTitle = 'Line Master';
  closeLink = '/master';

  managementCards: DashCard[] = [
    { id: 'configuration', title: 'Line Configuration', description: 'Create / configure production lines', route: 'Configuration', icon: 'fa-cogs', category: 'Line Master', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'checking', title: 'Line Checking', description: 'Check pending line configurations', route: 'Checking', icon: 'fa-clipboard-check', category: 'Line Master', gradient: 'linear-gradient(135deg, #f6d365 0%, #fda085 100%)' },
    { id: 'approval', title: 'Line Approval', description: 'Approve checked line configurations', route: 'Approval', icon: 'fa-check-circle', category: 'Line Master', gradient: 'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)' },
    { id: 'log', title: 'Line Configuration Log', description: 'Line log with update', route: 'Log', icon: 'fa-list', category: 'Line Master', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'map', title: 'Map Product', description: 'Map product to line', route: 'Map', icon: 'fa-link', category: 'Line Master', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
  ];

  searchQuery = '';
  selectedCategory = 'All';

  get categories(): string[] {
    return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))];
  }

  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q));
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' }
  ];
  selectedPalette = this.paletteOptions[2];
  showPalette = false;

  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try { localStorage.setItem('master_linemaster_palette', palette.name); } catch { }
  }

  constructor(public service: DataAccessService) {}

  ngOnInit(): void {
    const savedPalette = localStorage.getItem('master_linemaster_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
    this.scrollPageToTop();
  }

  selectCategory(cat: string, event?: Event): void {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
      const target = event.currentTarget as HTMLElement | null;
      target?.blur();
    }
    this.selectedCategory = cat;
    this.scrollPageToTop();
  }

  private scrollPageToTop(): void {
    try {
      if (typeof window !== 'undefined') {
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
      }
      if (typeof document !== 'undefined') {
        const main = document.querySelector('.content-area, .main-container, .clr-main-container, main');
        if (main) {
          (main as HTMLElement).scrollTop = 0;
        }
      }
    } catch {
      /* ignore */
    }
  }
}
