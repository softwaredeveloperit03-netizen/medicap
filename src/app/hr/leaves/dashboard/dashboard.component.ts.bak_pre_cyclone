import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DashCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  badgeCount?: () => number;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  pageTitle = 'Leave Management';
  sectionLabel = 'Leave Section';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
  ];
  selectedPalette: { name: string; swatch: string; background: string; cardShadow: string } = this.paletteOptions[0];

  cards: DashCard[] = [
    { id: 'applications', title: 'Leave Applications', description: 'Apply and manage leave applications', route: 'responsibility', icon: 'fa-calendar-plus', category: 'Leave', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'card', title: 'Leave Card', description: 'View leave card', route: 'password', icon: 'fa-id-card', category: 'Leave', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
    { id: 'record', title: 'Leave Record', description: 'View leave records', route: 'rights', icon: 'fa-clipboard-list', category: 'Leave', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
  ];

  results;
  employees;
  departments;
  department_name = '';
  emp_code = '';
  from_date = '';
  to_date = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getLeavesLog();
    this.getDepartments();
    this.getEmployees();
    const savedPalette = localStorage.getItem('hr_sub_dashboard_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }

  getCategories(): string[] {
    return ['All'].concat(Array.from(new Set(this.cards.map(c => c.category))));
  }

  getFilteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter(c => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().indexOf(q) !== -1 || (c.description && c.description.toLowerCase().indexOf(q) !== -1) || c.category.toLowerCase().indexOf(q) !== -1;
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_i: number, card: DashCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('hr_sub_dashboard_palette', palette.name);
    } catch (e) {}
  }

  getLeavesLog() {
    this.service.get('hr/leave.php?type=getLeavesLog&department_name=' + this.department_name + '&emp_code=' + this.emp_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  getEmployees() {
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + this.department_name).subscribe(response => {
      this.employees = response;
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
}
