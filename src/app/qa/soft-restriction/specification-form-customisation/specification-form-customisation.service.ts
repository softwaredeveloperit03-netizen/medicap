import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
import { SpecificationCustomisationLogMeta, SpecificationScope, SPEC_SCOPE_TO_FORM_CODE } from './specification-form-customisation.constants';

const API = 'softcust/gmp_customisation_form.php';

@Injectable({ providedIn: 'root' })
export class SpecificationFormCustomisationService {
  constructor(private service: DataAccessService) {}

  private code(scope: SpecificationScope): string {
    return SPEC_SCOPE_TO_FORM_CODE[scope];
  }

  getActiveLayout(scope: SpecificationScope): Observable<any | null> {
    return this.service.get(
      API + '?type=getActiveLayout&form_code=' + encodeURIComponent(this.code(scope))
    ) as Observable<any | null>;
  }

  getLog(scope: SpecificationScope): Observable<SpecificationCustomisationLogMeta[]> {
    return this.service.get(
      API + '?type=getLog&form_code=' + encodeURIComponent(this.code(scope))
    ) as Observable<SpecificationCustomisationLogMeta[]>;
  }

  getById(scope: SpecificationScope, id: number): Observable<SpecificationCustomisationLogMeta | null> {
    return this.service.get(
      API + '?type=getById&form_code=' + encodeURIComponent(this.code(scope)) + '&id=' + id
    ) as Observable<SpecificationCustomisationLogMeta | null>;
  }

  getPendingApprovals(scope: SpecificationScope): Observable<SpecificationCustomisationLogMeta[]> {
    return this.service.get(
      API + '?type=getPendingApprovals&form_code=' + encodeURIComponent(this.code(scope))
    ) as Observable<SpecificationCustomisationLogMeta[]>;
  }

  saveRequest(payload: {
    scope: SpecificationScope;
    entry_by: string;
    fields: unknown[];
  }): Observable<{ status: string; message?: string; id?: number; request_no?: string }> {
    return this.service.post(
      API + '?type=saveRequest&form_code=' + encodeURIComponent(this.code(payload.scope)),
      JSON.stringify(payload)
    ) as Observable<{ status: string; message?: string; id?: number; request_no?: string }>;
  }

  approve(scope: SpecificationScope, id: number, approvalBy?: string): Observable<{ status: string; message?: string }> {
    return this.service.post(
      API + '?type=approve&form_code=' + encodeURIComponent(this.code(scope)),
      JSON.stringify({ id, approval_by: approvalBy || '' })
    ) as Observable<{ status: string; message?: string }>;
  }

  reject(scope: SpecificationScope, id: number): Observable<{ status: string; message?: string }> {
    return this.service.post(
      API + '?type=reject&form_code=' + encodeURIComponent(this.code(scope)),
      JSON.stringify({ id })
    ) as Observable<{ status: string; message?: string }>;
  }
}
