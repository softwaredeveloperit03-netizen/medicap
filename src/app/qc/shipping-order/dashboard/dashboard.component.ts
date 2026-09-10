import { Component } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { SO_FORM_ID } from '../so.constants';

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
  selector: 'app-so-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent {
  dashboardTitle = 'QC Shipping Order';
  sectionLabel = 'Form ' + SO_FORM_ID + ' — Contract Lab Sample Shipping (WI-QC-005-08 §1.2–1.4)';
  closeRouterLink = '/qc';
  searchQuery = '';
  selectedCategory = 'All';

  paletteOptions = [
    { name: 'Aurora', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
  ];
  selectedPalette = this.paletteOptions[0];

  cards: DeptCard[] = [
    { id: 'new', title: 'New Shipping Order', description: 'Prepare FQC-005-09-A for contract lab shipment', route: 'new', icon: 'fa-shipping-fast', category: 'Paperwork', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'log', title: 'Shipping Register', description: 'All shipping order records', route: 'log', icon: 'fa-book', category: 'Paperwork', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'pending-check', title: 'Pending Check', description: 'Orders awaiting Checked By sign-off', route: 'log', icon: 'fa-clipboard-check', category: 'Workflow', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'results', title: 'QC Results Review', description: 'Review test results from contract labs (§1.4)', route: 'log', icon: 'fa-file-signature', category: 'Workflow', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
  ];

  constructor(public service: DataAccessService) {}

  getCategories(): string[] {
    return ['All', 'Paperwork', 'Workflow'];
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
