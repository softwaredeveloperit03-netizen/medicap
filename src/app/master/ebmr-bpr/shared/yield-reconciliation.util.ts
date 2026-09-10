/** Fixed Yield Reconciliation block driven from Configure Stage & Step (Applicable). */

export interface YieldReconciliationData {
  input_qty: string | number;
  final_qty: string | number;
  wastage: string | number;
  sampling: string | number;
  uom: string;
  remark: string;
}

export interface YieldReconciliationCalc {
  input: number | null;
  final: number | null;
  wastage: number | null;
  sampling: number | null;
  /** Final yield quantity (= final qty / weight). */
  yield_qty: number | null;
  /** Final yield % = (final / input) × 100. */
  yield_pct: number | null;
  /** Balance = input − final − wastage − sampling. */
  balance: number | null;
  accounted: number | null;
}

export function emptyYieldReconciliation(uom = 'kg'): YieldReconciliationData {
  return {
    input_qty: '',
    final_qty: '',
    wastage: '',
    sampling: '',
    uom,
    remark: '',
  };
}

export function defaultYieldReconciliationTemplate(): { enabled: boolean; uom: string; title: string } {
  return {
    enabled: false,
    uom: 'kg',
    title: 'Yield Reconciliation',
  };
}

function toNum(v: any): number | null {
  if (v === null || v === undefined || String(v).trim() === '') return null;
  const n = parseFloat(String(v).replace(/,/g, ''));
  return isNaN(n) ? null : n;
}

function round2(n: number): number {
  return Math.round(n * 100) / 100;
}

export function calcYieldReconciliation(data: Partial<YieldReconciliationData> | null | undefined): YieldReconciliationCalc {
  const input = toNum(data?.input_qty);
  const final = toNum(data?.final_qty);
  const wastage = toNum(data?.wastage);
  const sampling = toNum(data?.sampling);
  const yield_qty = final;
  const yield_pct = input != null && input !== 0 && final != null ? round2((final / input) * 100) : null;
  const accounted =
    final != null || wastage != null || sampling != null
      ? round2((final || 0) + (wastage || 0) + (sampling || 0))
      : null;
  const balance =
    input != null && (final != null || wastage != null || sampling != null)
      ? round2(input - (final || 0) - (wastage || 0) - (sampling || 0))
      : null;
  return { input, final, wastage, sampling, yield_qty, yield_pct, balance, accounted };
}

export function formatYieldNum(v: number | null | undefined, digits = 2): string {
  if (v == null || isNaN(v as number)) return '—';
  return Number(v).toFixed(digits);
}

export function normalizeApplicableFlag(v: any): string {
  const s = String(v || '').trim().toLowerCase();
  if (s === 'applicable' || s === 'yes' || s === 'y' || s === '1' || s === 'true') return 'Applicable';
  return 'Not Applicable';
}
