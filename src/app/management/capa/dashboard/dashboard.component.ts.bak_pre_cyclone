import { Component, OnInit } from '@angular/core';
import * as CanvasJS from 'src/assets/js/canvasjs.min';

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
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'CAPA';
  closeLink = '/management';

  managementCards: DashCard[] = [
    { id: 'new', title: 'New CAPA', description: 'Create new corrective action', route: 'new', icon: 'fa-plus-circle', category: 'Operations', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approve', title: 'CAPA For Review & Approval', description: 'Review and approve CAPA', route: 'approve', icon: 'fa-check-circle', category: 'Operations', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'logbook', title: 'CAPA Logbook', description: 'View CAPA logbook', route: 'logbook', icon: 'fa-book', category: 'Operations', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' }
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
      const matchSearch = !q || c.title.toLowerCase().includes(q) || c.category.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q));
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
  selectedPalette = this.paletteOptions[0];
  showPalette = false;
  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }
  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('management_capa_palette', palette.name);
    } catch { }
  }

  constructor() { }

  ngOnInit(): void {
    const savedPalette = localStorage.getItem('management_capa_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
    setTimeout(() => {
      const chart = new CanvasJS.Chart("chartContainer", {
        theme: "light2",
        animationEnabled: true,
        exportEnabled: true,
        data: [{
          type: "pie",
          toolTipContent: "<b>{name}</b>: Rs.{y} (#percent%)",
          indexLabel: "{name} - #percent%",
          dataPoints: [
            { y: 45, name: "CAPA For Incident" },
            { y: 20, name: "CAPA For Deviation" },
            { y: 35, name: "CAPA Investigation & Others" }
          ]
        }]
      });
      chart.render();
      const chart2 = new CanvasJS.Chart("chartContainer2", {
        theme: "light2",
        animationEnabled: true,
        exportEnabled: true,
        data: [{
          type: "pie",
          toolTipContent: "<b>{name}</b>: Rs.{y} (#percent%)",
          indexLabel: "{name} - #percent%",
          dataPoints: [
            { y: 20, name: "Process CAPA" },
            { y: 20, name: "Equipment Failure" },
            { y: 20, name: "Others" },
            { y: 20, name: "Material CAPA" },
            { y: 20, name: "Quality Failure" }
          ]
        }]
      });
      chart2.render();
    }, 100);
  }
}
