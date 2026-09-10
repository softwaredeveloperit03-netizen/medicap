import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface ProdCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
}

@Component({
  selector: 'app-webmr',
  templateUrl: './webmr.component.html',
  styleUrls: ['./webmr.component.css'],
})
export class WebmrComponent implements OnInit {
  dashboardTitle = 'Production Department';
  sectionLabel = 'Non eBMR (Masters)';
  closeRouterLink = '/fproduction';
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

  cards: ProdCard[] = [
    { id: 'dispensing', title: 'Dispensing', description: 'Material requisition / dispensing', route: '/production/batch/dispensing', icon: 'fa-prescription', category: 'Production', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'inprocess', title: 'Under Prod. Batches', description: 'Batches under production', route: '/production/batch/inprocess', icon: 'fa-cogs', category: 'Production', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  ];

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    const savedPalette = localStorage.getItem('fproduction_webmr_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) this.selectedPalette = found;
    }
  }

  getCategories(): string[] {
    return ['All'].concat(Array.from(new Set(this.cards.map(c => c.category))));
  }

  getFilteredCards(): ProdCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter(c => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().indexOf(q) !== -1 || (c.description && c.description.toLowerCase().indexOf(q) !== -1) || c.category.toLowerCase().indexOf(q) !== -1;
      return matchCategory && matchSearch;
    });
  }

  trackByCardId(_i: number, card: ProdCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('fproduction_webmr_palette', palette.name);
    } catch (e) {}
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights: any;
  loggedInDept: string | null = null;
  trainig_cordinator = 'No';

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          (this.loggedInDept || '')
      )
      .subscribe((response: any) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
        this.trainig_cordinator = this.rights[0].trainig_cordinator || 'No';
      });
  }
}
