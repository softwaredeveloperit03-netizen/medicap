import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ATR_WORKFLOW_STEPS } from '../atr.constants';

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
  selector: 'app-atr-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  dashboardTitle = 'Analytical Test Request';
  sectionLabel = 'Form FQC-005-06-A — Analytical Test Request';
  closeRouterLink = '/qc';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
  ];
  selectedPalette = this.paletteOptions[0];

  cards: DeptCard[] = [
    { id: 'new', title: 'New Request (Section A)', description: 'Requestor — initiate analytical test request', route: 'new', icon: 'fa-file-medical', category: 'Request', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Request Register', description: 'View all analytical test requests', route: 'log', icon: 'fa-book', category: 'Request', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    ...ATR_WORKFLOW_STEPS.map(s => ({
      id: s.route,
      title: s.title,
      description: s.description,
      route: s.route,
      icon: s.icon,
      category: 'Approval Workflow',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
    }))
  ];

  constructor(public service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.get_rights();
  }

  getCategories(): string[] {
    return ['All', 'Request', 'Approval Workflow'];
  }

  getFilteredCards(): DeptCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter(c => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().includes(q) || (c.description && c.description.toLowerCase().includes(q));
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_i: number, card: DeptCard): string {
    return card.id;
  }

  togglePalette(): void { this.showPalette = !this.showPalette; }

  setPalette(palette: typeof this.paletteOptions[0]): void {
    this.selectedPalette = palette;
    this.showPalette = false;
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
