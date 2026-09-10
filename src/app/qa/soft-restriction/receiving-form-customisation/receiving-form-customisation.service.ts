import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
import { RECEIVING_FORM_CODE, ReceivingCustomisationLogMeta } from './receiving-form-customisation.constants';

const API = 'softcust/gmp_customisation_form.php';

@Injectable({ providedIn: 'root' })
export class ReceivingFormCustomisationService {
  constructor(private service: DataAccessService) {}

  getActiveLayout(): Observable<any | null> {
    return this.service.get(
      API + '?type=getActiveLayout&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE)
    ) as Observable<any | null>;
  }

  getLog(): Observable<ReceivingCustomisationLogMeta[]> {
    return this.service.get(
      API + '?type=getLog&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE)
    ) as Observable<ReceivingCustomisationLogMeta[]>;
  }

  getById(id: number): Observable<ReceivingCustomisationLogMeta | null> {
    return this.service.get(
      API + '?type=getById&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE) + '&id=' + id
    ) as Observable<ReceivingCustomisationLogMeta | null>;
  }

  getPendingApprovals(): Observable<ReceivingCustomisationLogMeta[]> {
    return this.service.get(
      API + '?type=getPendingApprovals&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE)
    ) as Observable<ReceivingCustomisationLogMeta[]>;
  }

  saveRequest(payload: {
    entry_by: string;
    fields: unknown[];
  }): Observable<{ status: string; message?: string; id?: number; request_no?: string }> {
    return this.service.post(
      API + '?type=saveRequest&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE),
      JSON.stringify(payload)
    ) as Observable<{ status: string; message?: string; id?: number; request_no?: string }>;
  }

  approve(id: number, approvalBy?: string): Observable<{ status: string; message?: string }> {
    return this.service.post(
      API + '?type=approve&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE),
      JSON.stringify({ id, approval_by: approvalBy || '' })
    ) as Observable<{ status: string; message?: string }>;
  }

  reject(id: number): Observable<{ status: string; message?: string }> {
    return this.service.post(
      API + '?type=reject&form_code=' + encodeURIComponent(RECEIVING_FORM_CODE),
      JSON.stringify({ id })
    ) as Observable<{ status: string; message?: string }>;
  }
}
