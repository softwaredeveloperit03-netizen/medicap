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

interface SidebarTab {
  id: string;
  label: string;
  icon: string;
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
    this.get_rights();
    this.getPendingIndends();
    this.updateFilteredCards();
    const savedPalette = localStorage.getItem('purchase_indend_dashboard_palette');
    if (savedPalette) {
      const found = this.paletteOptions.find(p => p.name === savedPalette);
      this.selectedPalette = found || this.paletteOptions[0];
      this.cdr.markForCheck();
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
  selectedTabId = 'all';
  showPalette = false;
  filteredCards: DeptCard[] = [];
  unreadindent = 0;

  readonly sidebarTabs: SidebarTab[] = [
    { id: 'all', label: 'All Modules', icon: 'fa-th-large' },
    { id: 'new', label: 'New Requisition', icon: 'fa-plus-circle' },
    { id: 'checking', label: 'Requisition Checking', icon: 'fa-clipboard-check' },
    { id: 'rmpmpo', label: 'RM/PM Processing', icon: 'fa-cogs' },
    { id: 'approval', label: 'GM Processing', icon: 'fa-user-check' },
    { id: 'log', label: 'Requisitions Log', icon: 'fa-clipboard-list' },
    { id: 'correction', label: 'Correction', icon: 'fa-edit' },
    { id: 'edit', label: 'Edit Requisition', icon: 'fa-pen' },
    { id: 'merge', label: 'Merge Requisition', icon: 'fa-object-group' },
  ];

  private allCards: DeptCard[] = [
    { id: 'new', title: 'New Requisition', description: 'Create new requisition requests', route: 'raw/new', icon: 'fa-plus-circle', category: 'Requisition', gradient: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)' },
    { id: 'checking', title: 'Requisition Checking', description: 'Review and check submitted requisitions', route: 'raw/checking', icon: 'fa-clipboard-check', category: 'Requisition', gradient: 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)' },
    { id: 'rmpmpo', title: 'RM/PM Requisition Processing', description: 'Process raw and packing material requisitions', route: 'rmpmpo', icon: 'fa-cogs', category: 'Requisition', gradient: 'linear-gradient(135deg, #0d9488 0%, #0f766e 100%)' },
    { id: 'approval', title: 'GM Requisition Processing', description: 'Review and approve general requisitions', route: 'raw/approval', icon: 'fa-user-check', category: 'Requisition', gradient: 'linear-gradient(135deg, #4f46e5 0%, #4338ca 100%)' },
    { id: 'log', title: 'Requisitions Log', description: 'Browse requisition history and status', route: 'raw', icon: 'fa-clipboard-list', category: 'Requisition', gradient: 'linear-gradient(135deg, #475569 0%, #334155 100%)' },
    { id: 'correction', title: 'Correction', description: 'Correct submitted requisition records', route: 'raw/correction', icon: 'fa-edit', category: 'Requisition', gradient: 'linear-gradient(135deg, #d97706 0%, #b45309 100%)' },
    { id: 'edit', title: 'Edit Requisition', description: 'Edit pending requisition records', route: 'raw/edit', icon: 'fa-pen', category: 'Requisition', gradient: 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)' },
    { id: 'merge', title: 'Merge Requisition', description: 'Merge requisition log entries', route: 'raw/merge', icon: 'fa-object-group', category: 'Requisition', gradient: 'linear-gradient(135deg, #0369a1 0%, #0284c7 100%)' },
  ];

  paletteOptions = [
    { name: 'Corporate', swatch: '#f1f5f9', background: 'linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%)', cardShadow: '0 2px 12px rgba(15, 23, 42, 0.06)' },
    { name: 'Ocean', swatch: '#eff6ff', background: 'linear-gradient(180deg, #f0f9ff 0%, #eff6ff 100%)', cardShadow: '0 2px 12px rgba(37, 99, 235, 0.08)' },
    { name: 'Slate', swatch: '#e2e8f0', background: 'linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%)', cardShadow: '0 2px 12px rgba(51, 65, 85, 0.08)' },
    { name: 'Mist', swatch: '#f0fdf4', background: 'linear-gradient(180deg, #f8fafc 0%, #f0fdf4 100%)', cardShadow: '0 2px 12px rgba(16, 185, 129, 0.06)' },
    { name: 'Pearl', swatch: '#fafafa', background: 'linear-gradient(180deg, #ffffff 0%, #f8fafc 100%)', cardShadow: '0 2px 12px rgba(15, 23, 42, 0.05)' },
  ];
  selectedPalette = this.paletteOptions[0];

  get selectedTabLabel(): string {
    return this.sidebarTabs.find(t => t.id === this.selectedTabId)?.label || 'All Modules';
  }

  get moduleCount(): number {
    return this.allCards.length;
  }

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

  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getCheckedIndendsForNotification').subscribe((response: any) => {
      this.unreadindent = response['Pending_indent'] || 0;
      this.syncBadgesAndUpdate();
      this.cdr.markForCheck();
    });
  }

  private syncBadgesAndUpdate(): void {
    const byId: Record<string, number> = {
      checking: this.unreadindent,
      rmpmpo: this.unreadindent,
      approval: this.unreadindent,
    };
    this.allCards.forEach(c => {
      const n = byId[c.id];
      c.badge = n != null && n > 0 ? n : undefined;
    });
    this.updateFilteredCards();
  }

  updateFilteredCards(): void {
    const q = (this.searchQuery || '').trim().toLowerCase();
    this.filteredCards = this.allCards.filter(c => {
      const matchTab = this.selectedTabId === 'all' || c.id === this.selectedTabId;
      const matchSearch =
        !q ||
        c.title.toLowerCase().indexOf(q) !== -1 ||
        (c.description && c.description.toLowerCase().indexOf(q) !== -1) ||
        c.category.toLowerCase().indexOf(q) !== -1;
      return matchTab && matchSearch;
    });
    this.cdr.markForCheck();
  }

  onSearchChange(): void {
    this.updateFilteredCards();
  }

  selectTab(tabId: string): void {
    this.selectedTabId = tabId;
    this.updateFilteredCards();
  }

  getTabBadge(tabId: string): number | undefined {
    if (tabId === 'all') {
      return undefined;
    }
    const card = this.allCards.find(c => c.id === tabId);
    return card?.badge;
  }

  trackByCardId(_index: number, card: DeptCard): string {
    return card.id;
  }

  trackByTabId(_index: number, tab: SidebarTab): string {
    return tab.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
    this.cdr.markForCheck();
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem('purchase_indend_dashboard_palette', palette.name);
    } catch (e) {}
    this.cdr.markForCheck();
  }
}
