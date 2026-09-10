import { Component, HostListener, Input, OnInit, AfterViewInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, NavigationStart, Params, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from '../dept-navigation.service';
import {
  buildSidebarTabsFromCards,
  QC_DASHBOARD_PALETTES,
  QC_DEPT_TOOLBAR_LINKS,
  QC_SAMPLING_SHELL_CLASS,
} from './qc-module-dashboard.constants';
import {
  QcDashboardPalette,
  QcDeptCard,
  QcDeptToolbarLink,
  QcSidebarTab,
} from './qc-module-dashboard.models';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-qc-module-dashboard-shell',
  templateUrl: './qc-module-dashboard-shell.component.html',
  styleUrls: ['./qc-module-dashboard.layout.css'],
})
export class QcModuleDashboardShellComponent implements OnInit, AfterViewInit, OnDestroy {
  @Input() dashboardTitle = '';
  @Input() dashboardTitleKey?: string;
  @Input() sectionLabel = '';
  @Input() sectionLabelKey?: string;
  @Input() sidebarTitle = '';
  @Input() sidebarTitleKey?: string;
  @Input() closeRouterLink = '/';
  @Input() closeUseHistory = true;
  @Input() closeLabel = 'Close';
  @Input() closeLabelKey?: string;
  @Input() searchPlaceholder = 'Search modules';
  @Input() searchPlaceholderKey?: string;
  @Input() cards: QcDeptCard[] = [];
  @Input() sidebarTabs: QcSidebarTab[] = [];
  @Input() paletteStorageKey = 'qc_module_dashboard_palette';
  @Input() sidebarStorageKey = 'qc_module_sidebar_collapsed';
  @Input() deptToolbarLinks: QcDeptToolbarLink[] = QC_DEPT_TOOLBAR_LINKS;
  @Input() showTopPanel = false;
  /** When false, hide module tiles and show qcWorkspace projected content (e.g. router-outlet forms). */
  @Input() showModuleGrid = true;
  /** When set, sidebar category clicks navigate here if the module grid is hidden (child route open). */
  @Input() moduleHomeLink?: string;
  /** Extra class on root shell (e.g. dashboard-shell--store-challan). */
  @Input() shellClass = '';
  /** Palette name used when nothing is saved in localStorage. */
  @Input() defaultPaletteName = '';
  /** 'categories' = filter tabs; 'modules' = module list in sidebar */
  @Input() sidebarNavMode: 'categories' | 'modules' = 'categories';
  /** Kept for compatibility; Master/Security look is always used */
  @Input() themeVariant: 'marketing' | 'qc' = 'marketing';
  /** Hide quick-link toolbar row when empty or false */
  @Input() showDeptToolbar = false;

  /** Show SOP / Software Flow / Operation Guide icons in the header */
  @Input() showFloatingDocs = false;

  /** Hide sidebar badge and section footer module count */
  @Input() showModuleCount = true;

  /** Optional explicit guide keys; defaults derive from title / route / selected category */
  @Input() guideModuleId = '';
  @Input() guideSectionOverride = '';

  searchQuery = '';
  selectedCategory = 'All';
  showPalette = false;
  sidebarCollapsed = false;
  mobileSidebarOpen = false;
  private readonly sidebarBreakpointPx = 1024;

  readonly paletteOptions: QcDashboardPalette[] = QC_DASHBOARD_PALETTES;
  selectedPalette: QcDashboardPalette = this.paletteOptions[0];

  rights: any;
  dept_head = 'No';
  trainig_cordinator = 'No';
  loggedInDept: string | null = null;
  private navSub?: Subscription;

  get guideTitle(): string {
    return this.dashboardTitle || this.sidebarTitle || 'Module';
  }

  get guideSection(): string {
    if (this.guideSectionOverride) {
      return this.guideSectionOverride;
    }
    if (this.selectedCategory && this.selectedCategory !== 'All') {
      return this.selectedCategory;
    }
    return this.sectionLabel || this.dashboardTitle || '';
  }

  get resolvedGuideModuleId(): string {
    if (this.guideModuleId) {
      return this.guideModuleId;
    }
    const path = (this.router.url || '').split('?')[0];
    const parts = path.split('/').filter(Boolean);
    return parts[parts.length - 1] || parts[1] || '';
  }

  constructor(
    public service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    const fromQuery = String(this.route.snapshot.queryParamMap.get('cat') || '').trim();
    if (fromQuery) {
      this.selectedCategory = fromQuery === 'all' ? 'All' : fromQuery;
    }
    const savedPalette = localStorage.getItem(this.paletteStorageKey);
    if (savedPalette) {
      const found = this.paletteOptions.find((p) => p.name === savedPalette);
      if (found) {
        this.selectedPalette = found;
      }
    } else if (this.defaultPaletteName) {
      const fallback = this.paletteOptions.find((p) => p.name === this.defaultPaletteName);
      if (fallback) {
        this.selectedPalette = fallback;
      }
    }
    try {
      this.sidebarCollapsed = localStorage.getItem(this.sidebarStorageKey) === '1';
    } catch {
      /* ignore */
    }
    this.navSub = this.router.events
      .pipe(filter((e): e is NavigationStart => e instanceof NavigationStart))
      .subscribe(() => this.saveScrollPosition());
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

  toggleMobileSidebar(): void {
    this.mobileSidebarOpen = !this.mobileSidebarOpen;
  }

  closeMobileSidebar(): void {
    this.mobileSidebarOpen = false;
  }

  /** Category tabs excluding the synthetic "All" entry from buildSidebarTabsFromCards */
  get effectiveSidebarTabs(): QcSidebarTab[] {
    const tabs = this.sidebarTabs?.length ? this.sidebarTabs : buildSidebarTabsFromCards(this.cards || []);
    return tabs.filter((t) => t.category !== 'All');
  }

  countForCategory(category: string): number {
    if (category === 'All') {
      return (this.cards || []).length;
    }
    return (this.cards || []).filter((c) => c.category === category).length;
  }

  ngAfterViewInit(): void {
    this.restoreScrollPosition();
  }

  ngOnDestroy(): void {
    this.navSub?.unsubscribe();
    this.saveScrollPosition();
  }

  private scrollStorageKey(): string {
    return `${this.paletteStorageKey}_scrollY`;
  }

  private getScrollHost(): HTMLElement | null {
    if (typeof document === 'undefined') {
      return null;
    }
    return document.querySelector('.content-area') as HTMLElement | null;
  }

  private readSavedScroll(): number | null {
    try {
      const raw = sessionStorage.getItem(this.scrollStorageKey());
      if (raw == null) {
        return null;
      }
      const y = parseInt(raw, 10);
      return Number.isFinite(y) ? y : null;
    } catch {
      return null;
    }
  }

  private saveScrollPosition(): void {
    if (typeof window === 'undefined') {
      return;
    }
    const host = this.getScrollHost();
    const y = host?.scrollTop ?? window.scrollY ?? 0;
    try {
      sessionStorage.setItem(this.scrollStorageKey(), String(y));
    } catch {
      /* ignore */
    }
  }

  private restoreScrollPosition(): void {
    const y = this.readSavedScroll();
    if (y == null || y <= 0) {
      return;
    }
    const apply = () => {
      const host = this.getScrollHost();
      if (host) {
        host.scrollTop = y;
      } else if (typeof window !== 'undefined') {
        window.scrollTo(0, y);
      }
    };
    setTimeout(apply, 0);
    setTimeout(apply, 120);
    setTimeout(apply, 300);
  }

  toggleSidebar(): void {
    this.sidebarCollapsed = !this.sidebarCollapsed;
    try {
      localStorage.setItem(this.sidebarStorageKey, this.sidebarCollapsed ? '1' : '0');
    } catch {
      /* ignore */
    }
  }

  onCloseClick(event?: Event): void {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    const fallback = (this.closeRouterLink || '/').trim() || '/';
    if (this.closeUseHistory) {
      this.deptNav.goBack(this.route, fallback);
      return;
    }
    const path = fallback.startsWith('/') ? fallback : `/${fallback}`;
    void this.router.navigateByUrl(path);
  }

  selectCategory(cat: string): void {
    this.selectedCategory = cat;
    if (this.isNarrowViewport) {
      this.mobileSidebarOpen = false;
    }
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { cat: cat === 'All' ? 'all' : cat },
      queryParamsHandling: 'merge',
    });
    if (!this.showModuleGrid && this.moduleHomeLink) {
      this.router.navigateByUrl(this.moduleHomeLink);
    }
  }

  showAllTabs(): void {
    this.searchQuery = '';
    this.selectCategory('All');
  }

  get shellClasses(): string {
    const parts = [this.shellClass];
    if (this.themeVariant === 'marketing') {
      parts.push('dashboard-shell--marketing');
    }
    const close = (this.closeRouterLink || '').trim();
    if (close === '/hr' || close.startsWith('/hr/')) {
      parts.push('dashboard-shell--hr');
    }
    const routePath = (this.router.url || '').split('?')[0].split('#')[0];
    if (
      routePath.includes('/qc/sampling') ||
      close.includes('/qc/sampling') ||
      this.shellClass.includes('qc-sampling')
    ) {
      parts.push(QC_SAMPLING_SHELL_CLASS);
    }
    return parts.filter(Boolean).join(' ');
  }

  get effectiveToolbarLinks(): QcDeptToolbarLink[] {
    return this.showDeptToolbar ? this.deptToolbarLinks : [];
  }

  isToolbarLinkVisible(link: QcDeptToolbarLink): boolean {
    if (link.showWhen === 'dept_head') {
      return this.dept_head === 'Yes';
    }
    if (link.showWhen === 'training') {
      return this.trainig_cordinator === 'Yes';
    }
    return true;
  }

  getFilteredCards(): QcDeptCard[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    return this.cards.filter((c) => {
      const matchCategory = this.selectedCategory === 'All' || c.category === this.selectedCategory;
      const searchHaystack = [
        c.title,
        c.titleKey,
        c.searchText,
        c.category,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      const matchSearch = !q || searchHaystack.includes(q);
      return matchCategory && matchSearch;
    });
  }

  getSectionLabel(): string {
    if (this.selectedCategory === 'All') {
      return this.sectionLabel;
    }
    const tab = this.sidebarTabs.find((t) => t.category === this.selectedCategory);
    return tab?.label || this.selectedCategory;
  }

  getSectionLabelKey(): string | undefined {
    if (this.selectedCategory === 'All') {
      return this.sectionLabelKey;
    }
    const tab = this.sidebarTabs.find((t) => t.category === this.selectedCategory);
    return tab?.labelKey;
  }

  /** Pass current hub URL so sub-pages can close back here reliably. */
  linkQueryParams(_route?: string): Params {
    return this.deptNav.returnQueryParams();
  }

  trackByCardId(_i: number, card: QcDeptCard): string {
    return card.id;
  }

  trackBySidebarTab(_i: number, tab: QcSidebarTab): string {
    return tab.id;
  }

  trackByToolbarLink(_i: number, link: QcDeptToolbarLink): string {
    return link.id;
  }

  togglePalette(): void {
    this.showPalette = !this.showPalette;
  }

  setPalette(palette: QcDashboardPalette): void {
    this.selectedPalette = palette;
    this.showPalette = false;
    try {
      localStorage.setItem(this.paletteStorageKey, palette.name);
    } catch {
      /* ignore */
    }
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          encodeURIComponent(this.loggedInDept || '')
      )
      .subscribe((response: any) => {
        this.rights = response;
        if (this.rights && this.rights[0]) {
          this.dept_head = this.rights[0].dept_head || 'No';
          this.trainig_cordinator = this.rights[0].trainig_cordinator || 'No';
        }
      });
  }
}
