import { Component, HostListener, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

export interface EmployeeCategory {
  id: string;
  name: string;
  icon: string;
}

export interface DashCard {
  id: string;
  title: string;
  description: string;
  route: string;
  icon: string;
  category: string;
  gradient: string;
  categoryId: string;
}

const G = {
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  navy: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
  copper: 'linear-gradient(135deg, #7c2d12 0%, #c2410c 100%)',
};

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  searchQuery = '';
  selectedCategoryId = 'all';
  showPalette = false;
  mobileSidebarOpen = false;

  private readonly sidebarBreakpointPx = 1024;
  private readonly storageKey = 'employee_dashboard_category';
  private readonly paletteKey = 'employee_dashboard_palette';

  readonly categories: EmployeeCategory[] = [
    { id: 'leave', name: 'Leave', icon: 'fa-calendar-alt' },
    { id: 'payroll', name: 'Payroll', icon: 'fa-money-check-alt' },
    { id: 'other', name: 'Other', icon: 'fa-folder-open' },
  ];

  readonly managementCards: DashCard[] = [
    { id: 'pending-leavs', title: 'Leave Application', description: 'Apply for leave', route: '/employee-dashboard/pending-leavs', icon: 'fa-calendar-alt', category: 'Leave', gradient: G.blue, categoryId: 'leave' },
    { id: 'card-leave', title: 'Leave Card', description: 'View leave card', route: '/employee-dashboard/card_leave', icon: 'fa-id-card', category: 'Leave', gradient: G.teal, categoryId: 'leave' },
    { id: 'leave-status', title: 'Leave Status', description: 'Check leave status', route: '/employee-dashboard/leave_status', icon: 'fa-info-circle', category: 'Leave', gradient: G.indigo, categoryId: 'leave' },
    { id: 'payrole', title: 'Salary', description: 'Salary and payroll', route: '/employee-dashboard/payrole', icon: 'fa-money-check-alt', category: 'Payroll', gradient: G.amber, categoryId: 'payroll' },
    { id: 'interview', title: 'Interview', description: 'Interview module', route: '/employee-dashboard/interview', icon: 'fa-user-tie', category: 'Other', gradient: G.steel, categoryId: 'other' },
    { id: 'notice-alert', title: 'Notice & Alert', description: 'Notices and alerts', route: '/employee-dashboard/self-certificate', icon: 'fa-bell', category: 'Other', gradient: G.navy, categoryId: 'other' },
    { id: 'employee-dashboard-visitors', title: 'Visitors', description: 'Visitor management', route: '/employee-dashboard/visitors', icon: 'fa-calendar-day', category: 'Other', gradient: G.indigo, categoryId: 'other' },
    { id: 'outpass', title: 'Outpass', description: 'Outpass requests', route: '/employee-dashboard/outpass', icon: 'fa-id-card', category: 'Other', gradient: G.copper, categoryId: 'other' },
    { id: 'paperless-chat', title: 'Paperless Chat', description: 'Paperless chat', route: '/employee-dashboard/self-certificate', icon: 'fa-comments', category: 'Other', gradient: G.blue, categoryId: 'other' },
    { id: 'emails', title: 'Emails', description: 'Email notifications', route: '/employee-dashboard/self-certificate', icon: 'fa-envelope', category: 'Other', gradient: G.slate, categoryId: 'other' },
    { id: 'employee-dashboard-resignation-acceptance', title: 'Resignation', description: 'Resignation acceptance', route: '/employee-dashboard/resignation/acceptance', icon: 'fa-sign-out-alt', category: 'Other', gradient: G.teal, categoryId: 'other' },
    { id: 'duty', title: 'Outdoor Duty Form', description: 'Outdoor duty form', route: '/employee-dashboard/duty', icon: 'fa-briefcase', category: 'Other', gradient: G.amber, categoryId: 'other' },
    { id: 'apprisal', title: 'Appraisal Request', description: 'Appraisal request', route: '/employee-dashboard/apprisal', icon: 'fa-chart-line', category: 'Other', gradient: G.steel, categoryId: 'other' },
    { id: 'shifts', title: 'Current Shift', description: 'Current shift details', route: '/employee-dashboard/shifts', icon: 'fa-business-time', category: 'Other', gradient: G.navy, categoryId: 'other' },
    { id: 'individual', title: 'Attendance', description: 'Individual attendance', route: '/employee-dashboard/individual', icon: 'fa-user-check', category: 'Other', gradient: G.blue, categoryId: 'other' },
    { id: 'training', title: 'Training', description: 'Employee training', route: '/employee-dashboard/training', icon: 'fa-graduation-cap', category: 'Other', gradient: G.indigo, categoryId: 'other' },
    { id: 'jobres', title: 'Job Responsibility', description: 'Job responsibilities', route: '/employee-dashboard/jobres', icon: 'fa-tasks', category: 'Other', gradient: G.copper, categoryId: 'other' },
  ];

  paletteOptions: { name: string; swatch: string; background: string; cardShadow: string }[] = [
    { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
    { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
    { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
    { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
    { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
    { name: 'Sky', swatch: '#e0f2fe', background: 'linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%)', cardShadow: '0 10px 30px rgba(14,165,233,0.15)' },
    { name: 'Stone', swatch: '#e7e5e4', background: 'linear-gradient(135deg, #fafaf9 0%, #e7e5e4 100%)', cardShadow: '0 10px 30px rgba(120,113,108,0.12)' },
    { name: 'Indigo', swatch: '#e0e7ff', background: 'linear-gradient(135deg, #e0e7ff 0%, #eef2ff 100%)', cardShadow: '0 10px 30px rgba(99,102,241,0.18)' },
  ];
  selectedPalette = this.paletteOptions[0];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private deptNav: DeptNavigationService
  ) {}

  ngOnInit(): void {
    const fromQuery = String(this.route.snapshot.queryParamMap.get('cat') || '').trim();
    const fromStorage = (() => {
      try {
        return localStorage.getItem(this.storageKey) || '';
      } catch {
        return '';
      }
    })();
    const initial = fromQuery || fromStorage || 'all';
    if (initial === 'all' || this.categories.some((c) => c.id === initial)) {
      this.selectedCategoryId = initial;
    }
    const savedPalette = localStorage.getItem(this.paletteKey);
    if (savedPalette) {
      const found = this.paletteOptions.find((p) => p.name === savedPalette);
      if (found) {
        this.selectedPalette = found;
      }
    }
  }

  @HostListener('window:resize')
  onResize(): void {
    if (this.windowWidth() >= this.sidebarBreakpointPx) {
      this.mobileSidebarOpen = false;
    }
  }

  private windowWidth(): number {
    return typeof window !== 'undefined' ? window.innerWidth : this.sidebarBreakpointPx;
  }

  get isNarrowViewport(): boolean {
    return this.windowWidth() < this.sidebarBreakpointPx;
  }

  get currentCategory(): EmployeeCategory | undefined {
    return this.categories.find((c) => c.id === this.selectedCategoryId);
  }

  countForCategory(catId: string): number {
    if (catId === 'all') {
      return this.managementCards.length;
    }
    return this.managementCards.filter((c) => c.categoryId === catId).length;
  }

  selectCategory(catId: string): void {
    this.selectedCategoryId = catId;
    if (this.isNarrowViewport) {
      this.mobileSidebarOpen = false;
    }
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { cat: catId },
      queryParamsHandling: 'merge',
    });
    try {
      localStorage.setItem(this.storageKey, catId);
    } catch {
      /* ignore */
    }
  }

  showAllTabs(): void {
    this.selectedCategoryId = 'all';
    this.searchQuery = '';
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { cat: 'all' },
      queryParamsHandling: 'merge',
    });
    try {
      localStorage.setItem(this.storageKey, 'all');
    } catch {
      /* ignore */
    }
  }

  toggleMobileSidebar(): void {
    this.mobileSidebarOpen = !this.mobileSidebarOpen;
  }

  closeMobileSidebar(): void {
    this.mobileSidebarOpen = false;
  }

  getFilteredCards(): DashCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.managementCards.filter((c) => {
      const matchCat = this.selectedCategoryId === 'all' || c.categoryId === this.selectedCategoryId;
      const matchSearch =
        !q ||
        c.title.toLowerCase().includes(q) ||
        (c.description && c.description.toLowerCase().includes(q)) ||
        c.category.toLowerCase().includes(q);
      return matchCat && matchSearch;
    });
  }

  openCard(card: DashCard, event?: Event): void {
    if (event) {
      event.preventDefault();
    }
    void this.router.navigateByUrl(card.route).catch((err) => {
      console.error('Employee hub navigation failed:', card.route, err);
    });
  }

  trackByCardId(_index: number, card: DashCard): string {
    return card.id;
  }

  trackByCatId(_index: number, cat: EmployeeCategory): string {
    return cat.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: { name: string; swatch: string; background: string; cardShadow: string }): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem(this.paletteKey, palette.name);
    } catch {
      /* ignore */
    }
  }

  onCloseDashboard(): void {
    this.deptNav.goBack(this.route, '/');
  }
}
