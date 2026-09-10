/** Lazy-loaded purchase department root (see app.module `path: 'purchase'`). */
export const PURCHASE_ROOT = '/purchase';

/** Dashboard sidebar / close-back category labels (must match purchase dashboard). */
export const PURCHASE_DASHBOARD_CATEGORIES = {
  VENDOR: 'Vendor Registration',
  QUOTATION: 'Quotation',
  REQUISITION: 'Purchase Requisition',
  ORDER: 'Purchase',
  POST_RECEIVING: 'Post Receiving Status',
  REPORT: 'Purchase Report',
  STOCK: 'Stock',
} as const;

export type PurchaseDashboardCategory =
  (typeof PURCHASE_DASHBOARD_CATEGORIES)[keyof typeof PURCHASE_DASHBOARD_CATEGORIES];

/** RouterLink + queryParams for returning to the main purchase dashboard. */
export function purchaseDashboardClose(category: PurchaseDashboardCategory): {
  routerLink: string[];
  queryParams: { category: PurchaseDashboardCategory };
} {
  return { routerLink: [PURCHASE_ROOT], queryParams: { category } };
}

/** Build an absolute router link under `/purchase`. */
export function purchasePath(...segments: string[]): string {
  const parts = segments.map(s => String(s || '').replace(/^\/+|\/+$/g, '')).filter(Boolean);
  return parts.length ? `${PURCHASE_ROOT}/${parts.join('/')}` : PURCHASE_ROOT;
}

/** RouterLink array under `/purchase` (preferred for in-app navigation). */
export function purchaseRouteLink(...segments: string[]): string[] {
  const parts = segments.map(s => String(s || '').replace(/^\/+|\/+$/g, '')).filter(Boolean);
  return parts.length ? [PURCHASE_ROOT, ...parts] : [PURCHASE_ROOT];
}
