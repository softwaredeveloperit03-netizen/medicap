/** Shared MOA Type setting (Software Customisation). */
export type MoaTypeValue = 'GTP Process' | 'Specification Specific MOA';

export const MOA_TYPE_PARAM_LABEL = 'MOA Type';
export const MOA_TYPE_OPTIONS: MoaTypeValue[] = ['GTP Process', 'Specification Specific MOA'];

export function normalizeMoaType(value: any): MoaTypeValue | '' {
  const s = String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ');
  if (s === 'gtp process' || s === 'gtp' || s === 'test gtp' || s === 'test gtp process') {
    return 'GTP Process';
  }
  if (
    s === 'specification specific moa' ||
    s === 'specification specific' ||
    s === 'spec specific'
  ) {
    return 'Specification Specific MOA';
  }
  return '';
}

export function isGtpMoaType(value: any): boolean {
  const n = normalizeMoaType(value);
  return n === 'GTP Process' || n === '';
}

export function isSpecSpecificMoaType(value: any): boolean {
  return normalizeMoaType(value) === 'Specification Specific MOA';
}

/** Pick newest plant-matching MOA Type row from custimize response. */
export function pickMoaTypeFromRows(rows: any[], plantId: string): MoaTypeValue | '' {
  const list = Array.isArray(rows) ? rows : [];
  const filtered = list
    .filter((r) => String(r?.param_label || '').trim() === MOA_TYPE_PARAM_LABEL)
    .filter((r) => plantId === '' || String(r?.plant_id || '').trim() === plantId)
    .sort((a, b) => (Number(b?.id) || 0) - (Number(a?.id) || 0));
  if (!filtered.length) {
    return '';
  }
  return normalizeMoaType(filtered[0]?.param_value);
}
