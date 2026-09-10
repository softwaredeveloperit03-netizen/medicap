import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

interface DashCard { id: string; title: string; description: string; route: string; icon: string; category: string; gradient: string; }

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'lmr', title: 'Lot Manufacturing Record', route: 'lmr', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'bmr', title: 'Batch Manufacturing Record', route: 'bmr', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'production-report', title: 'Production Report', route: 'production-report', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

  pageTitle = 'Production Report';
  closeLink = '/management';
  managementCards: DashCard[] = [
    { id: 'production-report', title: 'Production Report', description: 'Production report', route: 'production-report', icon: 'fa-chart-bar', category: 'Reports', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'yield', title: 'Yield Statement', description: 'Yield statement', route: '/management/report/statement', icon: 'fa-file-invoice-dollar', category: 'Reports', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'plan-list', title: 'Plan List', description: 'Production plan list', route: '/management/production/plan-list', icon: 'fa-list', category: 'Reports', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' }
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
  setPalette(p: { name: string; background: string; cardShadow: string }): void { this.selectedPalette = p; this.showPalette = false; try { localStorage.setItem('management_production_palette', p.name); } catch { } }
  constructor() { }
  ngOnInit() { const s = localStorage.getItem('management_production_palette'); if (s) { const f = this.paletteOptions.find(x => x.name === s); if (f) this.selectedPalette = f; } }
}
