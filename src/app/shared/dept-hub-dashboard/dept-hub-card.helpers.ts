import { QcDeptCard } from '../qc-module-dashboard/qc-module-dashboard.models';

const GRADIENTS = [
  'linear-gradient(135deg, #334155 0%, #475569 100%)',
  'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
  'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
  'linear-gradient(135deg, #3730a3 0%, #6366f1 100%)',
  'linear-gradient(135deg, #b45309 0%, #d97706 100%)',
  'linear-gradient(135deg, #1e3a5f 0%, #64748b 100%)',
  'linear-gradient(135deg, #172554 0%, #1e3a8a 100%)',
  'linear-gradient(135deg, #7c2d12 0%, #c2410c 100%)',
];

export function pickDeptHubGradient(index: number): string {
  return GRADIENTS[index % GRADIENTS.length];
}

export function slugifyDeptHubId(value: string): string {
  return (value || 'item')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || 'item';
}

export function normalizeDeptHubRoute(route: string): string {
  const r = (route || '').trim();
  if (!r || r.startsWith('http')) {
    return r;
  }
  return r.replace(/^\//, '');
}

/** Build cards with rotating marketing-style gradients */
export function buildDeptHubCards(
  items: Array<{ title: string; route: string; icon?: string; category?: string }>
): QcDeptCard[] {
  return items.map((item, i) => ({
    id: slugifyDeptHubId(item.route || item.title),
    title: item.title,
    route: normalizeDeptHubRoute(item.route),
    icon: item.icon || 'fa-th-large',
    category: item.category || 'Modules',
    gradient: pickDeptHubGradient(i),
  }));
}

export function filterVisibleDeptHubCards(
  cards: QcDeptCard[],
  isVisible?: (card: QcDeptCard) => boolean
): QcDeptCard[] {
  if (!isVisible) {
    return cards;
  }
  return cards.filter(isVisible);
}
