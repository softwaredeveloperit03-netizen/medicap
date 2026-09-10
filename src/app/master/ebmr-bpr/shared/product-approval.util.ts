export interface ProductApprovalPage {
  label_claim: string;
  label_claim_sub: string;
  storage_condition: string;
  product_code: string;
  standard_batch_size: string;
  shelf_life: string;
  actual_batch_size: string;
  mfg_date: string;
  expiry_date: string;
  effective_date: string;
  review_date: string;
  effective_batch_no: string;
  mfg_formula_no: string;
}

export const PAP_MASTER_LOCKED_FIELDS: (keyof ProductApprovalPage)[] = ['label_claim', 'shelf_life'];

export const PAP_EXECUTION_FIELDS: (keyof ProductApprovalPage)[] = [
  'label_claim_sub',
  'storage_condition',
  'product_code',
  'standard_batch_size',
  'actual_batch_size',
  'mfg_date',
  'expiry_date',
  'effective_date',
  'review_date',
  'effective_batch_no',
  'mfg_formula_no',
];

export function emptyProductApprovalPage(): ProductApprovalPage {
  return {
    label_claim: '',
    label_claim_sub: '',
    storage_condition: '',
    product_code: '',
    standard_batch_size: '',
    shelf_life: '',
    actual_batch_size: '',
    mfg_date: '',
    expiry_date: '',
    effective_date: '',
    review_date: '',
    effective_batch_no: '',
    mfg_formula_no: '',
  };
}

/** Coerce legacy plain-text product_approval into structured object. */
export function normalizeProductApprovalPage(
  raw: unknown,
  header?: Record<string, string>
): ProductApprovalPage {
  const base = emptyProductApprovalPage();
  if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
    return { ...base, ...(raw as ProductApprovalPage) };
  }
  if (typeof raw === 'string' && raw.trim()) {
    base.label_claim = raw.trim();
  }
  return base;
}

/** Label claim & shelf life only — from Product Master / BMR header. */
export function applyMasterLockedFields(page: ProductApprovalPage, source: Record<string, any>): void {
  if (!page || !source) return;
  const lc = source['label_claim_text'] || source['label_claim'];
  if (lc !== undefined && lc !== null && String(lc).trim()) {
    page.label_claim = String(lc).trim();
  }
  const sl = source['shelf_life'];
  if (sl !== undefined && sl !== null && String(sl).trim()) {
    page.shelf_life = String(sl).trim();
  }
}

/** Clear fields that are entered only during batch execution. */
export function clearProductApprovalExecutionFields(page: ProductApprovalPage): void {
  PAP_EXECUTION_FIELDS.forEach((k) => (page[k] = ''));
}

/** @deprecated Use applyMasterLockedFields — kept for compatibility. */
export function applyHeaderToProductApproval(
  page: ProductApprovalPage,
  header: Record<string, any>,
  overwrite = true
): void {
  applyMasterLockedFields(page, header);
  if (!overwrite) return;
  clearProductApprovalExecutionFields(page);
}

export function productApprovalHasContent(page: ProductApprovalPage | null | undefined): boolean {
  if (!page) return false;
  return Object.values(page).some((v) => String(v || '').trim());
}
