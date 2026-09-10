import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

interface DeptCard {
  id: string;
  title: string;
  description?: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  badge?: number;
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

  ngOnInit(): void {
    this.get_rights();
    this.getPendingIndends();
    this.categories = ['All', ...Array.from(new Set(this.allCards.map(c => c.category)))];
    this.updateFilteredCards();
    const savedPalette = localStorage.getItem('store_challan_dashboard_palette');
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
  challanVeri = 0;

  private allCards: DeptCard[] = [
    { id: 'approval', title: 'Inwards From Security', description: 'Inwards from security', route: 'approval', icon: 'fa-sign-in-alt', category: 'Challan', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'verification', title: 'Challans Verification', description: 'Verify challans', route: 'verification', icon: 'fa-clipboard-check', category: 'Challan', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    { id: 'hold', title: 'On Hold', description: 'Challans on hold', route: 'hold', icon: 'fa-pause-circle', category: 'Challan', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'log', title: 'Log', description: 'Challan log', route: 'log', icon: 'fa-clipboard-list', category: 'Challan', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
  ];

  paletteOptions = [
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Lavender', swatch: '#ede9fe', background: 'linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%)', cardShadow: '0 10px 30px rgba(139,92,246,0.15)' },
  ];
  selectedPalette = this.paletteOptions[0];

  get_rights(): void {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + encodeURIComponent(this.loggedInDept || '')).subscribe({
      next: (response: any) => {
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
        this.cdr.markForCheck();
      },
      error: () => this.cdr.markForCheck()
    });
  }

  getPendingIndends(): void {
    this.service.get('notification.php?type=pendingChallanVeri').subscribe({
      next: (response: any) => {
        this.challanVeri = response?.['pendingChallanVeri'] ?? 0;
        if (this.challanVeri > 0 && response?.['text']) {
          alertify.warning(response['text']);
        }
        this.syncBadgesAndUpdate();
        this.cdr.markForCheck();
      },
      error: () => this.cdr.markForCheck()
    });
  }

  private syncBadgesAndUpdate(): void {
    const byId: Record<string, number> = { verification: this.challanVeri };
    this.allCards.forEach(c => {
      const n = byId[c.id];
      c.badge = n != null && n > 0 ? n : undefined;
    });
    this.updateFilteredCards();
  }

  updateFilteredCards(): void {
    const q = (this.searchQuery || '').trim().toLowerCase();
    this.filteredCards = this.allCards.filter(c => {
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
      localStorage.setItem('store_challan_dashboard_palette', palette.name);
    } catch (e) { /* ignore */ }
    this.cdr.markForCheck();
  }
}
