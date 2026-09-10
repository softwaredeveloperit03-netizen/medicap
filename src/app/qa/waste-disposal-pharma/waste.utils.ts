import { DatePipe } from '@angular/common';

export const FORM_NO = 'FQA-022-A';
export const SOP_REF = 'SOP-QA-022';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-04-01';
export const MANUAL_MATERIAL = '__manual__';

export const SOURCE_OF_WASTE_OPTIONS = [
  'QC Lab',
  'Production',
  'Formulation (PD)',
  'Analytical Lab',
  'Packaging/ printed components',
  'Normal Waste',
];

export const WASTE_TYPE_OPTIONS = ['Solid', 'Liquid', 'Solvent', 'Glassware'];

export interface WasteDisposalRow {
  material_mode: string;
  selected_material_key: string;
  waste_material_name: string;
  approximate_weight_kg: string;
  waste_type: string;
  initials: string;
  row_date: string;
  comments: string;
}

export interface MaterialOption {
  key: string;
  label: string;
  name: string;
  source: string;
}

export function defaultFromDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-01') || '';
}

export function defaultToDate(datePipe: DatePipe): string {
  return datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
}

export function getEmpDisplayName(): string {
  return (
    localStorage.getItem('emp_name') ||
    localStorage.getItem('username') ||
    localStorage.getItem('emp_id') ||
    ''
  );
}

export function createEmptyWasteRow(datePipe: DatePipe): WasteDisposalRow {
  return {
    material_mode: 'master',
    selected_material_key: '',
    waste_material_name: '',
    approximate_weight_kg: '',
    waste_type: '',
    initials: getEmpDisplayName(),
    row_date: datePipe.transform(new Date(), 'yyyy-MM-dd') || '',
    comments: '',
  };
}

export function createDefaultWasteRows(datePipe: DatePipe): WasteDisposalRow[] {
  return [createEmptyWasteRow(datePipe)];
}

export function materialLabel(item: any, source: string): string {
  const name = item?.product_name || item?.material_name || item?.name || '';
  const code = item?.product_code || item?.material_code || '';
  return code ? `${name} (${code}) — ${source}` : `${name} — ${source}`;
}
