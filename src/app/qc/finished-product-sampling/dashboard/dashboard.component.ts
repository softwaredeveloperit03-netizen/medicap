import { Component } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { FPS_FORM_TITLE, FPS_WORKFLOW_STEPS } from '../fps.constants';

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
  selector: 'app-fps-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent {
  dashboardTitle = FPS_FORM_TITLE;
  sectionLabel = 'Form FQC-005-12-A — Finished Product Sampling Plan';
  closeRouterLink = '/qc';
  searchQuery = '';
  selectedCategory = 'All';
  selectedPalette = { background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' };

  cards: DeptCard[] = [
    { id: 'new', title: 'New Sampling Plan', description: 'Create FQC-005-12-A for QA → Production', route: 'new', icon: 'fa-file-medical', category: 'Intake', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Sampling Register', description: 'All finished product sampling records', route: 'log', icon: 'fa-book', category: 'Intake', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    ...FPS_WORKFLOW_STEPS.map(s => ({
      id: s.route,
      title: s.title,
      description: s.description,
      route: s.route,
      icon: s.icon,
      category: 'Workflow',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
    }))
  ];

  constructor(public service: DataAccessService) {}

  getCategories(): string[] {
    return ['All', 'Intake', 'Workflow'];
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
