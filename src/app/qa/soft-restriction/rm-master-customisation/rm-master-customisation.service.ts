import { Injectable } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Observable } from 'rxjs';
import { RMCustomisationRecord } from './rm-master-customisation.constants';

const API = 'softcust/rm_master_customisation.php';

@Injectable({ providedIn: 'root' })
export class RmMasterCustomisationService {

  constructor(private service: DataAccessService) {}

  /** Get the active (approved & implemented) customisation for master/material form visibility */
  getActiveCustomisation(): Observable<RMCustomisationRecord | null> {
    return this.service.get(API + '?type=getActiveCustomisation') as Observable<RMCustomisationRecord | null>;
  }

  /** Get log of all customisation requests */
  getLog(): Observable<RMCustomisationRecord[]> {
    return this.service.get(API + '?type=getLog') as Observable<RMCustomisationRecord[]>;
  }

  /** Get single record by id for view */
  getById(id: number): Observable<RMCustomisationRecord> {
    return this.service.get(API + '?type=getById&id=' + id) as Observable<RMCustomisationRecord>;
  }

  /** Get pending approvals */
  getPendingApprovals(): Observable<RMCustomisationRecord[]> {
    return this.service.get(API + '?type=getPendingApprovals') as Observable<RMCustomisationRecord[]>;
  }

  /** Save new customisation request (status = Pending) */
  saveRequest(data: RMCustomisationRecord): Observable<{ status: string; message?: string; id?: number; request_no?: string }> {
    return this.service.post(API + '?type=saveRequest', JSON.stringify(data)) as Observable<{ status: string; message?: string; id?: number; request_no?: string }>;
  }

  /** Approve request and set as active (implement in QC module) */
  approve(id: number, approvalBy?: string): Observable<{ status: string; message?: string }> {
    return this.service.post(API + '?type=approve', JSON.stringify({ id, approval_by: approvalBy || '' })) as Observable<{ status: string; message?: string }>;
  }

  /** Reject request */
  reject(id: number): Observable<{ status: string; message?: string }> {
    return this.service.post(API + '?type=reject', JSON.stringify({ id })) as Observable<{ status: string; message?: string }>;
  }
}
