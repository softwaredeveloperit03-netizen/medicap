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
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  pageTitle = 'Checklists';
  closeLink = '/master';

  managementCards: DashCard[] = [
    { id: 'form', title: 'New Checklist', description: 'Create new checklist', route: 'form', icon: 'fa-clipboard-list', category: 'Checklist', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approve', title: 'Checklist Approval', description: 'Approve checklists', route: 'approve', icon: 'fa-clipboard-check', category: 'Checklist', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', showWhen: 'isapprover' },
    { id: 'log', title: 'Checklist Log', description: 'View checklist log', route: 'Log', icon: 'fa-clipboard-check', category: 'Checklist', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
  ];

  searchQuery = '';
  selectedCategory = 'All';
  isapprover = 'No';
  rights: any;
  loggedInDept: string;

  get categories(): string[] {
    return ['All', ...Array.from(new Set(this.managementCards.map(c => c.category)))];
  }

  get filteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      if (c.showWhen === 'isapprover' && this.isapprover !== 'Yes') return false;
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
  selectedPalette = this.paletteOptions[0];
  showPalette = false;

  togglePalette(): void { this.showPalette = !this.showPalette; }
  setPalette(palette: { name: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try { localStorage.setItem('master_checklist_palette', palette.name); } catch { }
  }

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department') || '';
  }

  ngOnInit(): void {
    this.get_rights();
    const savedPalette = localStorage.getItem('master_checklist_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }

  get_rights(): void {
    this.service
      .get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(localStorage.getItem('emp_id') || '') + '&dep_name=' + encodeURIComponent(this.loggedInDept))
      .subscribe((response: any) => {
        this.rights = response;
        if (Array.isArray(response) && response[0]) {
          this.isapprover = response[0].isapprover || 'No';
        }
      });
  }
}
