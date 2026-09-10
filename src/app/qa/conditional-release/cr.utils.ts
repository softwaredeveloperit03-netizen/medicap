import { DatePipe } from '@angular/common';

export const FORM_A_NO = 'FQA-020-A';
export const FORM_B_NO = 'FQA-020-B';
export const SOP_REF = 'SOP-QA-020';
export const REVISION_NO = '00';
export const EFFECTIVE_DATE = '2025-04-09';
export const MANUAL_PRODUCT = '__manual__';

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

export function displayApproverName(value: unknown): string {
  let text = String(value ?? '').trim();
  if (!text) {
    return '';
  }
  if (text.includes(' - ')) {
    text = text.split(' - ')[0].trim();
  }
  const parenEndMatch = text.match(/^(.+?)\s*\([^)]+\)\s*$/);
  if (parenEndMatch) {
    return parenEndMatch[1].trim();
  }
  const parenMatch = text.match(/^(.+?)\s*\([^)]+\)/);
  if (parenMatch) {
    return parenMatch[1].trim();
  }
  return text;
}

export function canRecordFinalRelease(record: any): boolean {
  return record?.status === 'Approved' && !record?.final_approval_by && !record?.final_approval_by_display;
}

export function statusClass(status: string): string {
  switch (status) {
    case 'Pending QA Head':
      return 'label-warning';
    case 'Approved':
    case 'Conditionally Released':
    case 'Fully Released':
      return 'label-success';
    case 'Rejected':
      return 'label-danger';
    case 'Declined':
      return 'label';
    default:
      return 'label-info';
  }
}
