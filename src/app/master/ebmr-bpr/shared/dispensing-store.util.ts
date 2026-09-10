export interface DispensingCheckRow {
  check_point: string;
  description?: string;
  evaluation_parameter?: string;
}

export interface DispensingSection {
  heading: string;
  rows: DispensingCheckRow[];
}

export interface DispensingMaterialRow {
  material: string;
  material_code?: string;
  qty: string;
  uom: string;
  stage?: string;
  function?: string;
  role?: string;
  dispensed_qty?: string;
  dispensing_by?: string;
  dispensing_date?: string;
  received_by?: string;
  received_on?: string;
  store_status?: string;
  ar_nos?: unknown;
  containers?: unknown;
}

export interface DispensingPage {
  sections: DispensingSection[];
  materials?: DispensingMaterialRow[];
  store_records?: DispensingMaterialRow[];
}

export interface DispensingPageMeta {
  product_code?: string;
  source?: string;
  unit_formula_id?: number;
  mfr_no?: string;
}

const EVAL_LABELS: Record<string, string> = {
  '1': 'Remarks',
  '2': 'Yes/No',
  '3': 'Ok/Not Ok',
  '4': 'Satisfactory/Not Satisfactory',
  '5': 'Applicable/Not Applicable',
  '6': 'Cleaned/Not Cleaned',
  '7': 'Sealed/ Unsealed',
  '8': 'Verified/ Not Verified',
  '9': 'Covered / Uncovered',
  '10': 'Exceptional / Above Average / Average/ Below Average / Unsatisfactory',
};

export function emptyDispensingPage(): DispensingPage {
  return { sections: [], materials: [] };
}

export function normalizeDispensingMaterial(raw: unknown): DispensingMaterialRow | null {
  if (!raw || typeof raw !== 'object') return null;
  const r = raw as Record<string, unknown>;
  const material = String(r['material'] ?? r['material_name'] ?? '').trim();
  const material_code = String(r['material_code'] ?? '').trim();
  if (!material && !material_code) return null;
  return {
    material: material || material_code,
    material_code: material_code || undefined,
    qty: String(r['qty'] ?? r['total_qty'] ?? '').trim(),
    uom: String(r['uom'] ?? r['unit'] ?? '').trim(),
    stage: String(r['stage'] ?? '').trim() || undefined,
    function: String(r['function'] ?? '').trim() || undefined,
    role: String(r['role'] ?? '').trim() || undefined,
  };
}

export function normalizeDispensingRow(raw: unknown): DispensingCheckRow | null {
  if (!raw || typeof raw !== 'object') return null;
  const r = raw as Record<string, unknown>;
  const check_point = String(r['check_point'] ?? r['checkpoint'] ?? '').trim();
  if (!check_point) return null;
  return {
    check_point,
    description: String(r['description'] ?? '').trim() || undefined,
    evaluation_parameter: String(r['evaluation_parameter'] ?? r['remark'] ?? '').trim() || undefined,
  };
}

export function normalizeDispensingSection(raw: unknown): DispensingSection | null {
  if (!raw || typeof raw !== 'object') return null;
  const s = raw as Record<string, unknown>;
  const heading = String(s['heading'] ?? s['checklist_heading'] ?? '').trim();
  const rowsRaw = s['rows'];
  const rows = Array.isArray(rowsRaw)
    ? rowsRaw.map(normalizeDispensingRow).filter((r): r is DispensingCheckRow => !!r)
    : [];
  if (!heading && !rows.length) return null;
  return { heading: heading || 'Dispensing', rows };
}

/** Accept legacy plain string or structured { sections } from profile.static.dispensing */
export function normalizeDispensingPage(raw: unknown): DispensingPage {
  if (typeof raw === 'string') {
    const text = raw.trim();
    return text
      ? { sections: [{ heading: 'Dispensing Instructions', rows: [{ check_point: text }] }] }
      : emptyDispensingPage();
  }
  if (raw && typeof raw === 'object') {
    const o = raw as Record<string, unknown>;
    const sections = Array.isArray(o['sections'])
      ? o['sections']
          .map(normalizeDispensingSection)
          .filter((s): s is DispensingSection => !!s && !!s.rows?.length)
      : [];
    const materials = Array.isArray(o['materials'])
      ? o['materials']
          .map(normalizeDispensingMaterial)
          .filter((m): m is DispensingMaterialRow => !!m)
      : [];
    const store_records = Array.isArray(o['store_records'])
      ? o['store_records']
          .map(normalizeDispensingMaterial)
          .filter((m): m is DispensingMaterialRow => !!m)
      : [];
    if (sections.length || materials.length || store_records.length) {
      return { sections, materials, store_records };
    }
  }
  return emptyDispensingPage();
}

export function dispensingHasContent(page: DispensingPage | null | undefined): boolean {
  if (!page) return false;
  if (page.materials?.length) return true;
  if (page.store_records?.length) return true;
  return !!page.sections?.some((s) => s.rows?.length);
}

export function dispensingMaterialsFromPage(page: DispensingPage | null | undefined): DispensingMaterialRow[] {
  return page?.materials?.length ? [...page.materials] : [];
}

export function evaluationParameterLabel(value: string | undefined): string {
  const v = String(value ?? '').trim();
  if (!v) return '—';
  return EVAL_LABELS[v] || v;
}

export function normalizeDispensingSections(raw: unknown): DispensingSection[] {
  if (!Array.isArray(raw)) return [];
  return raw
    .map(normalizeDispensingSection)
    .filter((s): s is DispensingSection => !!s && !!s.rows?.length);
}
