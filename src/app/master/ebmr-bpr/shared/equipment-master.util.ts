export interface EquipmentMasterRow {
  id?: string | number;
  equipment_code: string;
  equipment_name: string;
  serial_no?: string;
  department?: string;
  equipment_type?: string;
  make?: string;
  location?: string;
  status?: string;
  equipment_category?: string;
  min_capacity?: string;
  capacity?: string;
  capacityUnit?: string;
  unit?: string;
  from_range?: string;
  to_range?: string;
  calibration_required?: string;
}

export interface EquipmentSelectionRow {
  name: string;
  id_no: string;
  purpose: string;
  capacity: string;
  uom: string;
  equipment_code?: string;
  make?: string;
  calibration_applicable?: string;
}

export interface EquipmentStepRow {
  name: string;
  id_no: string;
  make: string;
  capacity: string;
  uom: string;
  equipment_code?: string;
  calibration_applicable?: string;
  calibration_required?: string;
}

const APPROVED_STATUSES = new Set(['approve', 'approved', 'active']);

export function normalizeCalibrationFlag(v: any): string {
  const s = String(v ?? '')
    .trim()
    .toLowerCase();
  if (s === 'applicable' || s === 'yes' || s === 'y' || s === '1' || s === 'true') return 'Applicable';
  return 'Not Applicable';
}

export function normalizeEquipmentMasterList(raw: unknown): EquipmentMasterRow[] {
  if (!Array.isArray(raw)) return [];
  return raw
    .filter((row) => row && typeof row === 'object')
    .map((row) => {
      const r = row as Record<string, unknown>;
      const idRaw = r['id'];
      const id =
        typeof idRaw === 'string' || typeof idRaw === 'number' ? idRaw : undefined;
      return {
        id,
        equipment_code: String(r['equipment_code'] ?? '').trim(),
        equipment_name: String(r['equipment_name'] ?? '').trim(),
        serial_no: String(r['serial_no'] ?? '').trim(),
        department: String(r['department'] ?? '').trim(),
        equipment_type: String(r['equipment_type'] ?? '').trim(),
        make: String(r['make'] ?? '').trim(),
        location: String(r['location'] ?? '').trim(),
        status: String(r['status'] ?? '').trim(),
        equipment_category: String(r['equipment_category'] ?? '').trim(),
        min_capacity: String(r['min_capacity'] ?? r['from_range'] ?? '').trim(),
        capacity: String(r['capacity'] ?? r['to_range'] ?? '').trim(),
        capacityUnit: String(r['capacityUnit'] ?? '').trim(),
        unit: String(r['unit'] ?? '').trim(),
        from_range: String(r['from_range'] ?? '').trim(),
        to_range: String(r['to_range'] ?? '').trim(),
        calibration_required: normalizeCalibrationFlag(r['calibration_required']),
      };
    })
    .filter((r) => r.equipment_code && r.equipment_name);
}

export function isApprovedEquipment(row: EquipmentMasterRow): boolean {
  const status = (row.status || '').trim().toLowerCase();
  if (status && !APPROVED_STATUSES.has(status)) return false;
  const cat = (row.equipment_category || '').trim().toLowerCase();
  if (cat === 'new purchase') return false;
  return true;
}

function isProcessOrProductionEquipment(row: EquipmentMasterRow): boolean {
  const parts = [row.department, row.equipment_type, row.equipment_category]
    .map((v) => (v || '').trim().toLowerCase())
    .filter(Boolean);
  return parts.some((p) => p.includes('process') || p.includes('production'));
}

/** Approved Process / Production equipment only (BMR equipment selection). */
export function filterBmrEquipmentMaster(rows: EquipmentMasterRow[]): EquipmentMasterRow[] {
  return rows
    .filter(isApprovedEquipment)
    .filter(isProcessOrProductionEquipment)
    .sort((a, b) => a.equipment_name.localeCompare(b.equipment_name));
}

export function equipmentMasterLabel(row: EquipmentMasterRow): string {
  const code = row.equipment_code ? ` · ${row.equipment_code}` : '';
  const cap = formatEquipmentCapacity(row);
  const uom = equipmentCapacityUom(row);
  const capTxt = cap ? ` · ${cap}${uom ? ' ' + uom : ''}` : '';
  return `${row.equipment_name}${code}${capTxt}`;
}

export function equipmentCapacityUom(row: EquipmentMasterRow): string {
  return (row.capacityUnit || row.unit || '').trim();
}

export function formatEquipmentCapacity(row: EquipmentMasterRow | EquipmentSelectionRow): string {
  const min = String((row as EquipmentMasterRow).min_capacity ?? (row as EquipmentMasterRow).from_range ?? '').trim();
  const max = String(row.capacity ?? (row as EquipmentMasterRow).to_range ?? '').trim();
  if (min && max && min !== max) return `${min} - ${max}`;
  return max || min || '';
}

export function equipmentToSelectionRow(row: EquipmentMasterRow): EquipmentSelectionRow {
  const purposeParts = [row.department, row.equipment_type].filter(Boolean);
  const cal = normalizeCalibrationFlag(row.calibration_required);
  return {
    name: row.equipment_name,
    id_no: row.equipment_code || row.serial_no || '',
    purpose: purposeParts.join(' · '),
    capacity: formatEquipmentCapacity(row),
    uom: equipmentCapacityUom(row),
    equipment_code: row.equipment_code,
    make: row.make || '',
    calibration_applicable: cal,
  };
}

export function equipmentToStepRow(row: EquipmentMasterRow): EquipmentStepRow {
  const cal = normalizeCalibrationFlag(row.calibration_required);
  return {
    name: row.equipment_name,
    id_no: row.equipment_code || row.serial_no || '',
    make: row.make || '',
    capacity: formatEquipmentCapacity(row),
    uom: equipmentCapacityUom(row),
    equipment_code: row.equipment_code,
    calibration_applicable: cal,
    calibration_required: cal,
  };
}

export function findEquipmentByCode(rows: EquipmentMasterRow[], code: string): EquipmentMasterRow | undefined {
  const c = (code || '').trim();
  if (!c) return undefined;
  return rows.find((r) => r.equipment_code === c);
}

export function findEquipmentSelectionByCode(
  rows: EquipmentSelectionRow[],
  code: string
): EquipmentSelectionRow | undefined {
  const c = (code || '').trim();
  if (!c) return undefined;
  return (rows || []).find((r) => (r.equipment_code || r.id_no || '').trim() === c);
}

export function equipmentSelectionLabel(row: EquipmentSelectionRow): string {
  const code = row.equipment_code || row.id_no;
  const codeTxt = code ? ` · ${code}` : '';
  const cap = String(row.capacity || '').trim();
  const uom = String(row.uom || '').trim();
  const capTxt = cap ? ` · ${cap}${uom ? ' ' + uom : ''}` : '';
  return `${row.name || '—'}${codeTxt}${capTxt}`;
}

export function selectionRowToStepRow(row: EquipmentSelectionRow): EquipmentStepRow {
  const cal = normalizeCalibrationFlag(row.calibration_applicable);
  return {
    name: row.name || '',
    id_no: row.equipment_code || row.id_no || '',
    make: row.make || '',
    capacity: row.capacity || '',
    uom: row.uom || '',
    equipment_code: row.equipment_code || row.id_no || '',
    calibration_applicable: cal,
    calibration_required: cal,
  };
}
