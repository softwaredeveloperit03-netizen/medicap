/** Shared helpers for master-style dashboard sidebars (category → nav + theme). */

export type MasterNavTheme =
  | 'qa'
  | 'qc'
  | 'purchase'
  | 'production'
  | 'stores'
  | 'other'
  | 'forms'
  | 'hr'
  | 'engineering';

export interface DashboardSidebarEntry {
  id: string;
  name: string;
  icon: string;
  theme: MasterNavTheme;
  /** Same values as category filter: 'All' or exact category label */
  filterKey: string;
}

const THEME_CYCLE: MasterNavTheme[] = [
  'qa',
  'qc',
  'purchase',
  'production',
  'stores',
  'other',
  'forms',
  'hr',
  'engineering',
];

export function slugifyLabel(label: string): string {
  return (
    label
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '') || 'cat'
  );
}

export function themeAtIndex(index: number): MasterNavTheme {
  return THEME_CYCLE[index % THEME_CYCLE.length];
}

/**
 * Build sidebar rows from category labels (first is usually 'All').
 */
export function sidebarFromCategories(
  categories: string[],
  iconFor: (label: string, index: number) => string
): DashboardSidebarEntry[] {
  return categories.map((label, i) => ({
    id: label === 'All' ? 'all' : slugifyLabel(label),
    name: label === 'All' ? 'All' : label,
    icon: iconFor(label, i),
    theme: themeAtIndex(i),
    filterKey: label,
  }));
}
