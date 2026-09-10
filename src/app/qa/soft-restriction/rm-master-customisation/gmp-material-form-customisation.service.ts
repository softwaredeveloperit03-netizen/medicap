import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
import { GmpActiveLayoutResponse } from './material-master-form-layout.constants';

const API = 'softcust/gmp_customisation_form.php';

export interface GmpFormLogMeta {
  id?: number;
  request_no?: string;
  revision_number?: string;
  entry_by?: string;
  entry_date?: string;
  approval_by?: string;
  approval_date?: string;
  status?: string;
  form_code?: string;
  fields?: unknown[];
}

@Injectable({ providedIn: 'root' })
export class GmpMaterialFormCustomisationService {
  constructor(private service: DataAccessService) {}

  getActiveLayout(): Observable<GmpActiveLayoutResponse | null> {
    return this.service.get(API + '?type=getActiveLayout') as Observable<GmpActiveLayoutResponse | null>;
  }

  getLog(): Observable<GmpFormLogMeta[]> {
    return this.service.get(API + '?type=getLog') as Observable<GmpFormLogMeta[]>;
  }

  getById(id: number): Observable<GmpFormLogMeta | null> {
    return this.service.get(API + '?type=getById&id=' + id) as Observable<GmpFormLogMeta | null>;
  }

  getPendingApprovals(): Observable<GmpFormLogMeta[]> {
    return this.service.get(API + '?type=getPendingApprovals') as Observable<GmpFormLogMeta[]>;
  }

  saveRequest(payload: { entry_by: string; fields: unknown[] }): Observable<{ status: string; message?: string; id?: number; request_no?: string }> {
    return this.service.post(API + '?type=saveRequest', JSON.stringify(payload)) as Observable<{
      status: string;
      message?: string;
      id?: number;
      request_no?: string;
    }>;
  }

  approve(id: number, approvalBy?: string): Observable<{ status: string; message?: string }> {
    return this.service.post(API + '?type=approve', JSON.stringify({ id, approval_by: approvalBy || '' })) as Observable<{
      status: string;
      message?: string;
    }>;
  }

  reject(id: number): Observable<{ status: string; message?: string }> {
    return this.service.post(API + '?type=reject', JSON.stringify({ id })) as Observable<{ status: string; message?: string }>;
  }
}
