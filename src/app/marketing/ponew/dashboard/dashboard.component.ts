import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface DeptCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  showWhen?: 'isuser' | 'isapprover';
}

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit {
  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getOrders();
    this.get_rights();
    this.categories = ['All', ...Array.from(new Set(this.allCards.map(c => c.category)))];
    this.updateFilteredCards();
    const savedPalette = localStorage.getItem('marketing_ponew_dashboard_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      if (found) {
        this.selectedPalette = found;
        this.cdr.markForCheck();
      }
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
  rights: any;
  loggedInDept: string | null = null;

  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;
  categories: string[] = ['All'];
  filteredCards: DeptCard[] = [];

  private allCards: DeptCard[] = [
    { id: 'new', title: 'New Forecast', description: 'Create new forecast entry', route: 'new', icon: 'fa-plus-circle', category: 'PO', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhen: 'isuser' },
    { id: 'approval', title: 'PO Approval', description: 'Approve purchase orders', route: 'approval', icon: 'fa-check-circle', category: 'PO', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', showWhen: 'isapprover' },
    { id: 'log', title: 'PO Log', description: 'View PO log', route: 'log', icon: 'fa-book', category: 'PO', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'fo', title: 'Forecast', description: 'Forecast view', route: 'fo', icon: 'fa-chart-line', category: 'PO', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'summery', title: 'Order Summary', description: 'Order summary report', route: 'summery', icon: 'fa-list-alt', category: 'PO', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
  ];

  paletteOptions = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
  ];
  selectedPalette = this.paletteOptions[0];

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || '')
      )
      .subscribe((response: any) => {
        this.rights = response;
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.isuser = r.isuser || 'No';
        this.ischecker = r.ischecker || 'No';
        this.isapprover = r.isapprover || 'No';
        this.qms_approver = r.qms_approver || 'No';
        this.dept_head = r.dept_head || 'No';
        this.isauditor = r.isauditor || 'No';
        this.plant_head = r.plant_head || 'No';
        this.shift_allocator = r.shift_allocator || 'No';
        this.updateFilteredCards();
        this.cdr.markForCheck();
      });
  }

  getOrders() {
    this.service
      .get('marketing/po.php?type=getOrdersChart')
      .subscribe(() => {});
  }

  updateFilteredCards(): void {
    const q = (this.searchQuery || '').trim().toLowerCase();
    this.filteredCards = this.allCards.filter(c => {
      if (c.showWhen === 'isuser' && this.isuser !== 'Yes') return false;
      if (c.showWhen === 'isapprover' && this.isapprover !== 'Yes') return false;
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const matchSearch = !q || c.title.toLowerCase().indexOf(q) !== -1 || (c.description && c.description.toLowerCase().indexOf(q) !== -1) || c.category.toLowerCase().indexOf(q) !== -1;
      return matchCategory && matchSearch;
    });
    this.cdr.markForCheck();
  }

  onSearchChange(): void {
    this.updateFilteredCards();
  }

  selectCategory(cat: string): void {
    this.selectedCategory = cat;
    this.updateFilteredCards();
  }

  trackByCardId(_index: number, card: DeptCard): string {
    return card.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
    this.cdr.markForCheck();
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('marketing_ponew_dashboard_palette', palette.name);
    } catch (e) {}
    this.cdr.markForCheck();
  }
}
