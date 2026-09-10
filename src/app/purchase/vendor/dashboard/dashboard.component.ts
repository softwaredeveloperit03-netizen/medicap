import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
import { purchasePath } from '../../purchase-route.constants';

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
    private cdr: ChangeDetectorRef,
    private masterHubReturn: MasterHubReturnService,
    private router: Router
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    this.getPendingVendor();
    this.getQaReturnedVendorCount();
    this.categories = ['All', ...Array.from(new Set(this.allCards.map(c => c.category)))];
    this.updateFilteredCards();
    const savedPalette = localStorage.getItem('purchase_vendor_dashboard_palette');
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
  unreadVendor = 0;
  qaReturnEditCount = 0;

  private allCards: DeptCard[] = [
    { id: 'registration', title: 'Vendor Registration', description: 'Register new vendor', route: purchasePath('vendor', 'registration'), icon: 'fa-user-plus', category: 'Vendor', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', showWhen: 'isuser' },
    { id: 'approval', title: 'Vendor For Approval', description: 'Approve pending vendors', route: purchasePath('vendor', 'approval'), icon: 'fa-check', category: 'Vendor', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', showWhen: 'isapprover' },
    { id: 'forEditing', title: 'Vendors From QA (Edit)', description: 'Correct data returned by QA, then resubmit for approval', route: purchasePath('vendor', 'for-editing'), icon: 'fa-edit', category: 'Vendor', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
    { id: 'log', title: 'Vendor Log', description: 'Vendor log', route: purchasePath('vendor', 'log'), icon: 'fa-file-alt', category: 'Vendor', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'approvedLog', title: 'Approved Vendor Log', description: 'Approved vendors log', route: purchasePath('vendor', 'approvedVendorLog'), icon: 'fa-bullhorn', category: 'Vendor', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    { id: 'map', title: 'Map Material', description: 'Map material to vendor', route: purchasePath('vendor', 'material', 'map'), icon: 'fa-link', category: 'Vendor', gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)' },
    { id: 'npdRequest', title: 'New Vendor Registration Request From NPD', description: 'Vendor registration request from NPD', route: '/rnd/master/material/venRegiRequest', icon: 'fa-link', category: 'Vendor', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
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
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') + '&dep_name=' + encodeURIComponent(this.loggedInDept || '')).subscribe((response: any) => {
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
      this.syncBadgesAndUpdate();
      this.cdr.markForCheck();
    });
  }

  getPendingVendor() {
    this.service.get('purchase/vendor.php?type=getPendingVendorsNotification').subscribe((response: any) => {
      this.unreadVendor = response['Pending_Vendor'] || 0;
      if (this.unreadVendor > 0 && typeof alertify !== 'undefined') {
        alertify.warning(response['text']);
      }
      this.syncBadgesAndUpdate();
      this.cdr.markForCheck();
    });
  }

  getQaReturnedVendorCount(): void {
    this.service.get('purchase/vendor.php?type=getVendorsReturnedByQaCount').subscribe((response: any) => {
      this.qaReturnEditCount = response && typeof response.count === 'number' ? response.count : 0;
      this.syncBadgesAndUpdate();
      this.cdr.markForCheck();
    });
  }

  private syncBadgesAndUpdate(): void {
    this.allCards.forEach(c => {
      if (c.id === 'approval' && this.unreadVendor > 0) {
        c.badge = this.unreadVendor;
      } else if (c.id === 'forEditing' && this.qaReturnEditCount > 0) {
        c.badge = this.qaReturnEditCount;
      } else {
        c.badge = undefined;
      }
    });
    this.updateFilteredCards();
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
      localStorage.setItem('purchase_vendor_dashboard_palette', palette.name);
    } catch (e) {}
    this.cdr.markForCheck();
  }

  /** From Master hub → back to Purchase tab; else Purchase home */
  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/purchase', { category: 'Vendor Registration' });
  }

  openCard(card: DeptCard, event?: Event): void {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    const route = card?.route || '';
    if (!route) {
      return;
    }
    void this.router.navigateByUrl(route).catch((err) => {
      console.error('Vendor hub navigation failed:', route, err);
    });
  }
}
