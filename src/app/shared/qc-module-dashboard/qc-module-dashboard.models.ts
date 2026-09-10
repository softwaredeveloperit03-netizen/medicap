export interface QcDeptCard {
  id: string;
  title?: string;
  titleKey?: string;
  searchText?: string;
  route?: string;
  externalUrl?: string;
  icon: string;
  category: string;
  gradient: string;
  badge?: number;
}

export interface QcSidebarTab {
  id: string;
  label: string;
  labelKey?: string;
  icon: string;
  category: string;
  /** Optional sidebar button background (gradient or solid) */
  background?: string;
}

export interface QcDeptToolbarLink {
  id: string;
  label: string;
  labelKey?: string;
  route: string;
  icon: string;
  showWhen?: 'always' | 'dept_head' | 'training';
}

export interface QcDashboardPalette {
  name: string;
  swatch: string;
  background: string;
  cardShadow: string;
}
