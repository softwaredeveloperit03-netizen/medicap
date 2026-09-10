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
  showWhen?: string;
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Production Plan';
  closeLink = '/management';

  managementCards: DashCard[] = [
    { id: 'new', title: 'New Costing', description: 'Create new costing', route: 'new', icon: 'fa-dollar-sign', category: 'Finance', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhen: 'user' },
    { id: 'actual', title: 'Actual Costing', description: 'Actual costing data', route: 'actual', icon: 'fa-file-invoice-dollar', category: 'Finance', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', showWhen: 'user' },
    { id: 'log', title: 'Costing Report', description: 'Costing reports', route: 'log', icon: 'fa-chart-line', category: 'Reports', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' }
  ];

  searchQuery = '';
  selectedCategory = 'All';
  get categories(): string[] {
    return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))];
  }
  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    const byRights = this.managementCards.filter(c => {
      if (c.showWhen === 'user' && this.isuser !== 'Yes') return false;
      return true;
    });
    return byRights.filter((c) => {
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
    try { localStorage.setItem('management_costing_palette', palette.name); } catch { }
  }

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.get_rights();
    const savedPalette = localStorage.getItem('management_costing_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }




}
