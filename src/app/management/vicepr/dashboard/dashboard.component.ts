import { Component, OnInit } from '@angular/core';

interface DashCard { id: string; title: string; description: string; route: string; icon: string; category: string; gradient: string; }

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'CFO Approvals';
  closeLink = '/management';

  managementCards: DashCard[] = [
    { id: 'poapproval', title: 'Po Approval', description: 'Purchase order approvals', route: 'poapproval', icon: 'fa-shopping-basket', category: 'Approvals', gradient: 'linear-gradient(135deg, #10b981 0%, #059669 100%)' }
  ];

  searchQuery = '';
  selectedCategory = 'All';
  get categories(): string[] { return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))]; }
  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => (this.selectedCategory === 'All' || c.category === this.selectedCategory) && (!q || c.title.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q))));
  }
  trackByCardId(_i: number, c: DashCard): string { return c.id; }

  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' }
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;
  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(p: { name: string; background: string; cardShadow: string }): void { this.selectedPalette = p; this.showPalette = false; try { localStorage.setItem('management_vicepr_palette', p.name); } catch { } }

  constructor() { }
  ngOnInit(): void { const s = localStorage.getItem('management_vicepr_palette'); if (s) { const f = this.paletteOptions.find(x => x.name === s); if (f) this.selectedPalette = f; } }
}
