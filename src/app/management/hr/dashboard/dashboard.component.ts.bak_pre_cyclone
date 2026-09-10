import { Component, OnInit } from '@angular/core';

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
  pageTitle = 'HR Management';
  closeLink = '/management';

  managementCards: DashCard[] = [
    { id: 'employee', title: 'Employee Details', description: 'Employee data and details', route: 'employee', icon: 'fa-users', category: 'HR', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'attendance', title: 'Employee Attendance', description: 'Attendance records', route: 'attendance', icon: 'fa-calendar-alt', category: 'HR', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'individual', title: 'Individual Attendance', description: 'Individual attendance view', route: 'individual', icon: 'fa-user', category: 'HR', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'leave-record', title: 'Leave Record', description: 'Leave records', route: 'leave-record', icon: 'fa-user', category: 'HR', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'salary', title: 'Salary Report', description: 'Salary reports', route: 'salary', icon: 'fa-file-invoice-dollar', category: 'HR', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
    { id: 'working', title: 'Total Man Working Hours', description: 'Man working hours', route: 'working', icon: 'fa-clock', category: 'HR', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
    { id: 'tds', title: 'TDS Statement', description: 'TDS projection statement', route: '/account/TDSProejctionStatement', icon: 'fa-file-alt', category: 'Finance', gradient: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)' }
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
  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try { localStorage.setItem('management_hr_palette', palette.name); } catch { }
  }

  constructor() { }

  ngOnInit() {
    const savedPalette = localStorage.getItem('management_hr_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }
}
