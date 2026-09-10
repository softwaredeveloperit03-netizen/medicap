import { DispensingMaterialRow } from './dispensing-store.util';

export interface UnitFormulaMasterSummary {
  id: number;
  mfr_no?: string;
  product_code?: string;
  product_name?: string;
  dosage_form?: string;
  batch_size?: string;
  unit?: string;
  formula_for?: string;
  version_no?: string;
}

export interface UnitFormulaRow {
  material: string;
  material_code?: string;
  qty: string;
  uom: string;
  function: string;
  stage?: string;
  process?: string;
  process_step?: string;
  role?: string;
}

const DISPENSING_STAGE_RE =
  /dispensing\s*(in|for)\s*production|dispensing\s*in\s*prod|^\s*dispensing\s*$/i;

export function unitFormulaMasterLabel(f: UnitFormulaMasterSummary): string {
  const mfr = (f.mfr_no || '').trim();
  const prod = (f.product_name || f.product_code || '').trim();
  const batch = [f.batch_size, f.unit].filter(Boolean).join(' ').trim();
  const parts = [mfr, prod, batch].filter(Boolean);
  return parts.length ? parts.join(' · ') : `Formula #${f.id}`;
}

export function normalizeUnitFormulaRows(raw: unknown): UnitFormulaRow[] {
  if (!Array.isArray(raw)) return [];
  return raw
    .filter((row) => row && typeof row === 'object')
    .map((row) => {
      const r = row as Record<string, unknown>;
      const stage = String(r['stage'] ?? '').trim();
      const process = String(r['process'] ?? r['process_step'] ?? '').trim();
      const role = String(r['role'] ?? '').trim();
      let fn = String(r['function'] ?? '').trim();
      if (!fn && role) fn = role;
      else if (!fn && process) fn = process;
      else if (!fn && stage) fn = stage;
      return {
        material: String(r['material'] ?? r['material_name'] ?? '').trim(),
        material_code: String(r['material_code'] ?? '').trim(),
        qty: String(r['qty'] ?? r['total_qty'] ?? '').trim(),
        uom: String(r['uom'] ?? r['unit'] ?? '').trim(),
        function: fn,
        stage: stage || undefined,
        process: process || undefined,
        process_step: String(r['process_step'] ?? '').trim() || undefined,
        role: role || undefined,
      };
    })
    .filter((r) => r.material || r.material_code);
}

/** True when unit formula line is tagged for Dispensing in Production (stage / process). */
export function isDispensingInProductionRow(row: UnitFormulaRow): boolean {
  const parts = [row.stage, row.process, row.process_step, row.function, row.role]
    .filter(Boolean)
    .join(' ');
  if (!parts.trim()) return false;
  if (DISPENSING_STAGE_RE.test(parts)) return true;
  const lower = parts.toLowerCase();
  return lower.includes('dispensing') && lower.includes('production');
}

export function filterDispensingInProductionRows(rows: UnitFormulaRow[]): UnitFormulaRow[] {
  return (rows || []).filter(isDispensingInProductionRow);
}

export function unitFormulaRowToDispensingMaterial(row: UnitFormulaRow): DispensingMaterialRow {
  return {
    material: row.material,
    material_code: row.material_code,
    qty: row.qty,
    uom: row.uom,
    stage: row.stage,
    function: row.function,
    role: row.role,
  };
}

export function buildDispensingMaterialsFromUnitFormula(rows: UnitFormulaRow[]): DispensingMaterialRow[] {
  return filterDispensingInProductionRows(rows).map(unitFormulaRowToDispensingMaterial);
}
