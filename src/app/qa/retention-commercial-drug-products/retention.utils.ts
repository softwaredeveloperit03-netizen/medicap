import { DatePipe } from '@angular/common';
import { Observable } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';

export const ADD_NEW_UNIT = 'Add New';

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

export function stampNow(datePipe: DatePipe): string {
  return getEmpDisplayName() + ' - ' + datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
}

export function loadUnits(service: DataAccessService): Observable<any> {
  return service.get('common.php?type=getUnits_List');
}

export function saveUnitMaster(service: DataAccessService, unit: string): Observable<any> {
  return service.post('master/unit.php?type=saveUnit', JSON.stringify({ unit: unit.trim().toUpperCase() }));
}
