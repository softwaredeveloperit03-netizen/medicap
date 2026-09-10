import { DatePipe } from '@angular/common';

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

export function hasStamp(value: unknown): boolean {
  return !!(value && String(value).trim());
}
