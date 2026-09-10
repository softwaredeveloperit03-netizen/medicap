import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { PLS_FORM_ID, PLS_WORKFLOW_STEPS } from '../pls.constants';

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
  selector: 'app-pls-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  dashboardTitle = 'Processing Laboratory Samples';
  sectionLabel = 'Form ' + PLS_FORM_ID + ' — Sampling and QC Release Form (Raw Materials)';
  closeRouterLink = '/qc';
  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;

  paletteOptions = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Seafoam', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
  ];
  selectedPalette = this.paletteOptions[0];

  cards: DeptCard[] = [
    { id: 'new', title: 'New Sample Intake', description: 'Create FQC-005-08-A from GRN / quarantine batch', route: 'new', icon: 'fa-file-medical', category: 'Intake', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Sample Register', description: 'All processing laboratory sample records', route: 'log', icon: 'fa-book', category: 'Intake', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    ...PLS_WORKFLOW_STEPS.map(s => ({
      id: s.route,
      title: s.title,
      description: s.description,
      route: s.route,
      icon: s.icon,
      category: 'Approval Workflow',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
    }))
  ];

  constructor(public service: DataAccessService) {}

  ngOnInit() {}

  getCategories(): string[] {
    return ['All', 'Intake', 'Approval Workflow'];
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
}
