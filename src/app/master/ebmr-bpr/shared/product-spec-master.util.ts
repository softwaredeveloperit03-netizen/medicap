export interface ProductSpecMasterSummary {
  id: number;
  specification_no: string;
  stpNo?: string;
  version_no?: string;
  product_code?: string;
  product_name?: string;
  dosage_form?: string;
  grade?: string;
}

export interface ProductSpecRow {
  parameter: string;
  specification: string;
  method: string;
  test_id?: string | number;
}

export function productSpecMasterLabel(s: ProductSpecMasterSummary): string {
  const parts = [
    (s.specification_no || '').trim(),
    (s.version_no || '').trim() ? 'v' + s.version_no : '',
    (s.product_name || s.product_code || '').trim(),
  ].filter(Boolean);
  return parts.length ? parts.join(' · ') : `Spec #${s.id}`;
}

export function normalizeProductSpecRows(raw: unknown): ProductSpecRow[] {
  if (!Array.isArray(raw)) return [];
  return raw
    .filter((row) => row && typeof row === 'object')
    .map((row) => {
      const r = row as Record<string, unknown>;
      const testId = r['test_id'];
      return {
        parameter: String(r['parameter'] ?? '').trim(),
        specification: String(r['specification'] ?? '').trim(),
        method: String(r['method'] ?? '').trim(),
        test_id:
          typeof testId === 'string' || typeof testId === 'number' ? testId : undefined,
      };
    })
    .filter((r) => r.parameter || r.specification);
}
