import { Component, OnInit } from '@angular/core';

interface DashCard { id: string; title: string; description: string; route: string; icon: string; category: string; gradient: string; }

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Purchase Orders';
  closeLink = '/management';



  managementCards: DashCard[] = [
    { id: 'po', title: 'Purchase Order', description: 'Purchase Orders', route: 'raw', icon: 'fa-box-open', category: 'Purchase', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'quotn-comp', title: 'Quotn Comptive Chart', description: 'Quotation comparative chart', route: '/purchase/quotation/comparative', icon: 'fa-chart-bar', category: 'Purchase', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
    { id: 'quotation', title: 'Quotation', description: 'Quotation management', route: 'quotation', icon: 'fa-file-invoice-dollar', category: 'Purchase', gradient: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)' }
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
  setPalette(p: { name: string; background: string; cardShadow: string }): void { this.selectedPalette = p; this.showPalette = false; try { localStorage.setItem('management_purchase_palette', p.name); } catch { } }
  constructor() { }
  ngOnInit() { const s = localStorage.getItem('management_purchase_palette'); if (s) { const f = this.paletteOptions.find(x => x.name === s); if (f) this.selectedPalette = f; } }
}
