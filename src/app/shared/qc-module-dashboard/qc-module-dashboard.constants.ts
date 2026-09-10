import { QcDashboardPalette, QcDeptToolbarLink, QcSidebarTab, QcDeptCard } from './qc-module-dashboard.models';

/** Root class for QC Sampling hub sidebars (readable nav on dark panel). */
export const QC_SAMPLING_SHELL_CLASS = 'dashboard-shell--qc-sampling';

export const QC_CARD_GRADIENTS = {
  slate: 'linear-gradient(135deg, #334155 0%, #475569 100%)',
  blue: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  teal: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  indigo: 'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  navy: 'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
  steel: 'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  violet: 'linear-gradient(135deg, #5b21b6 0%, #8b5cf6 100%)',
  emerald: 'linear-gradient(135deg, #047857 0%, #10b981 100%)',
  amber: 'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)',
  rose: 'linear-gradient(135deg, #be123c 0%, #f43f5e 100%)',
};

export const QC_DASHBOARD_PALETTES: QcDashboardPalette[] = [
  { name: 'Clean', swatch: '#e7eff9', background: 'linear-gradient(135deg, #eaf1fb 0%, #dde8f6 100%)', cardShadow: '0 1px 3px rgba(18,45,90,0.06)' },
  { name: 'Aurora', swatch: '#eef2ff', background: 'linear-gradient(135deg, #eef2ff 0%, #f5f7fa 100%)', cardShadow: '0 10px 30px rgba(102,126,234,0.15)' },
  { name: 'Sunrise', swatch: '#fff9f0', background: 'linear-gradient(135deg, #fff9f0 0%, #f7f8fc 100%)', cardShadow: '0 10px 30px rgba(255,183,94,0.18)' },
  { name: 'Seafoam', swatch: '#ccfbf1', background: 'linear-gradient(135deg, #f0fffa 0%, #f4f7ff 100%)', cardShadow: '0 10px 30px rgba(56,189,158,0.16)' },
  { name: 'Slate', swatch: '#f1f5f9', background: 'linear-gradient(135deg, #f7f9fb 0%, #eef2f7 100%)', cardShadow: '0 10px 30px rgba(59,66,82,0.12)' },
  { name: 'Sky', swatch: '#e0f2fe', background: 'linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%)', cardShadow: '0 10px 30px rgba(14,165,233,0.15)' },
];

export const QC_DEPT_TOOLBAR_LINKS: QcDeptToolbarLink[] = [
  { id: 'calibration', label: 'Calibration', labelKey: 'qc.links.calibration', route: '/calibration', icon: 'fa-ruler-combined', showWhen: 'always' },
  { id: 'training', label: 'Training', labelKey: 'qc.links.training', route: '/training', icon: 'fa-chalkboard-teacher', showWhen: 'training' },
  { id: 'pm', label: 'PM Intimation', labelKey: 'qc.links.pmIntimation', route: '/preventiveimain', icon: 'fa-wrench', showWhen: 'always' },
  { id: 'dept-head', label: 'Dept Head', labelKey: 'qc.links.deptHead', route: '/hrfordepthead', icon: 'fa-user-tie', showWhen: 'dept_head' },
  { id: 'indent', label: 'Indent', labelKey: 'qc.links.indent', route: '/indend/raw', icon: 'fa-file-alt', showWhen: 'always' },
  { id: 'qms', label: 'QMS', labelKey: 'qc.links.qms', route: '/qa/qms', icon: 'fa-shield-alt', showWhen: 'always' },
  { id: 'breakdown', label: 'Breakdown', labelKey: 'qc.links.breakdown', route: '/breakdown', icon: 'fa-tools', showWhen: 'always' },
];

const CATEGORY_ICONS: Record<string, string> = {
  Workflow: 'fa-cogs',
  Records: 'fa-archive',
  Documents: 'fa-file-alt',
  'Inward / Outward': 'fa-exchange-alt',
  Masters: 'fa-database',
  Other: 'fa-ellipsis-h',
  Procurement: 'fa-truck',
  Receiving: 'fa-truck',
  Processing: 'fa-cogs',
  Inventory: 'fa-boxes',
  Operations: 'fa-tools',
  Forms: 'fa-file-alt',
  'Main Modules': 'fa-th-large',
  Planning: 'fa-calendar-alt',
  STP: 'fa-project-diagram',
  'STP Sections': 'fa-project-diagram',
  'Planning Sections': 'fa-tasks',
  Indent: 'fa-file-alt',
  'Stock Book': 'fa-book',
  'Purchase Status': 'fa-shopping-cart',
  'Factory Order': 'fa-industry',
  'SOP Initiation': 'fa-file-alt',
  'New SOP & Revision': 'fa-file',
  'Training & Implementation': 'fa-chalkboard-teacher',
  Training: 'fa-graduation-cap',
  QMS: 'fa-shield-alt',
  Instruments: 'fa-microscope',
  'Column Management': 'fa-columns',
  'Solution Preparation': 'fa-flask',
  'Analysis & QA': 'fa-chart-line',
  Lifecycle: 'fa-project-diagram',
  Proficiency: 'fa-vial',
  Approval: 'fa-stamp',
  Certification: 'fa-award',
  Logs: 'fa-archive',
  'Review & Approval': 'fa-stamp',
  Modules: 'fa-th-large',
  Master: 'fa-book',
  'RM/PM Testing': 'fa-vial',
  Stability: 'fa-hourglass-half',
  'In Process': 'fa-cogs',
  'Finished Goods': 'fa-box',
  'OOS Review': 'fa-search',
};

export function buildSidebarTabsFromCards(cards: QcDeptCard[]): QcSidebarTab[] {
  const tabs: QcSidebarTab[] = [
    { id: 'all', label: 'All Modules', labelKey: 'common.allTabs', icon: 'fa-layer-group', category: 'All' },
  ];
  const categories = Array.from(new Set(cards.map((c) => c.category)));
  for (const category of categories) {
    tabs.push({
      id: category.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
      label: category,
      icon: CATEGORY_ICONS[category] || 'fa-folder',
      category,
    });
  }
  return tabs;
}
