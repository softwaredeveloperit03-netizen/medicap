import { Component, OnInit } from '@angular/core';

interface DashCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Garden Management';
  closeLink = '/admin';
  managementCards: DashCard[] = [
    { id: 'daily-work', title: 'Daily Work Record', description: 'Record daily garden work', route: 'daily-work', icon: 'fa-calendar-day', category: 'Garden', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'resources', title: 'Demand for Resources', description: 'Demand for garden resources', route: 'resources', icon: 'fa-boxes', category: 'Garden', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'plant-count', title: 'Plant Count', description: 'Maintain plant count register', route: 'plant-count', icon: 'fa-seedling', category: 'Garden', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'cleaning-log', title: 'Cleaning Log', description: 'Garden and area cleaning log', route: 'cleaning-log', icon: 'fa-broom', category: 'Garden', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' }
  ];
  searchQuery = '';
  selectedCategory = 'All';
  get categories(): string[] { return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))]; }
  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q));
      return matchCategory && matchSearch;
    });
  }
  trackByCardId(_index: number, card: DashCard): string { return card.id; }
  paletteOptions: { name: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' }
  ];
  selectedPalette = this.paletteOptions[0];
  showPalette = false;
  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try { localStorage.setItem('admin_gardev_palette', palette.name); } catch { }
  }
  constructor() { }
  ngOnInit(): void {
    const savedPalette = localStorage.getItem('admin_gardev_palette');
    if (savedPalette) { const found = this.paletteOptions.find(p => p.name === savedPalette); if (found) this.selectedPalette = found; }
  }
}
