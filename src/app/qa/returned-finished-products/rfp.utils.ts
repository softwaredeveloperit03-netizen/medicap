import { DatePipe } from '@angular/common';

export const FORM_A_NO = 'FQA-023-A';
export const FORM_B_NO = 'FQA-023-B';
export const SOP_REF = 'SOP-QA-023';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-04-16';
export const MANUAL_PRODUCT = '__manual__';

export const DESTRUCTION_CRITERIA = [
  'All expired, short-dated and outdated products',
  'Soiled, damaged product',
  'Partial units',
  'Incomplete packaging space (missing cartons, inserts)',
  'Products stored in improper conditions in customer storage area or during shipping',
];

export const RETURN_STOCK_CRITERIA = [
  'Refused Shipments',
  'Items in unopened cases',
  'High cost/demand units in unopened cases',
];

export const SALVAGING_CRITERIA = [
  'Laboratory tests show product meets identity, strength, quality and purity',
  'Inspection of premises: drug product and packaging not compromised in improper storage conditions',
  'Accumulate returns into BIO-MED waste shippers. BIO-MED disposes waste shippers',
];

export const DISPOSITION_OPTIONS = ['Return to Stock', 'Rejected'];
export const NARCOTIC_OPTIONS = ['Narcotic', 'Non-Narcotic'];

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

export function stampNow(datePipe: DatePipe): string {
  return getEmpDisplayName() + ' - ' + datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
}

export function statusClass(status: string): string {
  switch (status) {
    case 'Pending QA':
      return 'label-warning';
    case 'Completed':
      return 'label-success';
    default:
      return 'label-info';
  }
}

export function createChecklistDefaults(length: number): boolean[] {
  return Array(length).fill(false);
}

export function buildChecklistPayload(destruction: boolean[], returnStock: boolean[], salvaging: boolean[]) {
  return {
    destruction,
    return_to_stock: returnStock,
    salvaging,
  };
}

export function parseChecklist(record: any): { destruction: boolean[]; returnStock: boolean[]; salvaging: boolean[] } {
  const checklist = record?.assessment_checklist || {};
  return {
    destruction: Array.isArray(checklist.destruction)
      ? checklist.destruction
      : createChecklistDefaults(DESTRUCTION_CRITERIA.length),
    returnStock: Array.isArray(checklist.return_to_stock)
      ? checklist.return_to_stock
      : createChecklistDefaults(RETURN_STOCK_CRITERIA.length),
    salvaging: Array.isArray(checklist.salvaging)
      ? checklist.salvaging
      : createChecklistDefaults(SALVAGING_CRITERIA.length),
  };
}
