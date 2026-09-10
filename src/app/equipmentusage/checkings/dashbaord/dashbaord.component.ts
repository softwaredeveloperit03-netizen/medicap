import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DeptCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

@Component({
  selector: 'app-dashbaord',
  templateUrl: './dashbaord.component.html',
  styleUrls: ['./dashbaord.component.css']
})
export class DashbaordComponent implements OnInit {
  dashboardTitle = 'Checkings';
  sectionLabel = 'Checkings Section';
  closeRouterLink = '/equipmentusage';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
    { name: 'Mint', swatch: '#d1fae5', background: 'linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%)', cardShadow: '0 10px 30px rgba(16,185,129,0.15)' },
  ];
  selectedPalette: { name: string; swatch: string; background: string; cardShadow: string } = this.paletteOptions[0];

  cards: DeptCard[] = [
    { id: 'Cleaning', title: 'Cleaning Checking', route: 'Cleaning', icon: 'fa-broom', category: 'Checkings', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'Usage', title: 'Usage Checking', route: 'Usage', icon: 'fa-tachometer-alt', category: 'Checkings', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'CleaningLog', title: 'Cleaning Log', route: 'CleaningLog', icon: 'fa-book', category: 'Logs', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
    { id: 'UsageLog', title: 'Usage Log', route: 'UsageLog', icon: 'fa-clipboard-list', category: 'Logs', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
  ];

  constructor(public service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.get_rights();
    const savedPalette = localStorage.getItem('equipmentusage_checkings_dashboard_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }

  getCategories(): string[] {
    return ['All'].concat(Array.from(new Set(this.cards.map(c => c.category))));
  }

  getFilteredCards(): DeptCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter(c => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().indexOf(q) !== -1 || (c.description && c.description.toLowerCase().indexOf(q) !== -1) || c.category.toLowerCase().indexOf(q) !== -1;
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_i: number, card: DeptCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('equipmentusage_checkings_dashboard_palette', palette.name);
    } catch (e) {}
  }

  rights: any;
  dept_head = 'No';
  trainig_cordinator = 'No';
  loggedInDept: string | null = null;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + encodeURIComponent(this.loggedInDept || ''))
      .subscribe((response: any) => {
        this.rights = response;
        if (this.rights && this.rights[0]) {
          this.dept_head = this.rights[0].dept_head || 'No';
          this.trainig_cordinator = this.rights[0].trainig_cordinator || 'No';
        }
      });
  }
}
