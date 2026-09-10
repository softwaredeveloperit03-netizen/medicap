export const HPLC_COMPLIANCE = [
  'ICH Q2(R2)',
  'USP <621>',
  'USP <1058>',
  'USP <1225>',
  'EU GMP Annex 15',
  'FDA 21 CFR 211.194',
];

export const STANDARD_COLUMN_TYPES = [
  { code: 'C18', phase: 'Octadecylsilane', usp: 'L1', typical: '4.6×250 mm, 5 µm' },
  { code: 'C8', phase: 'Octylsilane', usp: 'L7', typical: '4.6×250 mm, 5 µm' },
  { code: 'C4', phase: 'Butylsilane', usp: 'L26', typical: '4.6×150 mm, 5 µm' },
  { code: 'PHENYL', phase: 'Phenyl', usp: 'L11', typical: '4.6×250 mm, 5 µm' },
  { code: 'CN', phase: 'Cyano', usp: 'L20', typical: '4.6×250 mm, 5 µm' },
  { code: 'NH2', phase: 'Aminopropyl', usp: 'L8', typical: '4.6×250 mm, 5 µm' },
  { code: 'SCX', phase: 'Strong cation exchange', usp: 'L9', typical: '4.6×250 mm, 5 µm' },
  { code: 'SAX', phase: 'Strong anion exchange', usp: 'L13', typical: '4.6×250 mm, 5 µm' },
  { code: 'HILIC', phase: 'HILIC amide', usp: '—', typical: '4.6×250 mm, 3 µm' },
  { code: 'SEC', phase: 'Size exclusion', usp: 'L39', typical: '7.8×300 mm' },
];

export const REGEN_METRIC_SPECS = [
  { key: 'plates', label: 'Theoretical plates (N)', spec: '≥ 2000', unit: '' },
  { key: 'tailing', label: 'Tailing factor (T)', spec: '≤ 2.0', unit: '' },
  { key: 'resolution', label: 'Resolution (Rs)', spec: '≥ 2.0', unit: '' },
  { key: 'back_pressure', label: 'Back pressure', spec: '≤ 300 bar', unit: 'bar' },
  { key: 'retention_shift', label: 'RT shift vs reference', spec: '≤ 2%', unit: '%' },
];

export const SST_PARAMETERS = [
  { param: 'Repeatability (%RSD)', spec: '≤ 2.0%', ich: 'ICH Q2' },
  { param: 'Tailing factor (T)', spec: '≤ 2.0', ich: 'USP <621>' },
  { param: 'Theoretical plates (N)', spec: '≥ 2000', ich: 'USP <621>' },
  { param: 'Resolution (Rs)', spec: '≥ 2.0', ich: 'USP <621>' },
  { param: 'Signal-to-noise', spec: '≥ 10', ich: 'ICH Q2' },
];
