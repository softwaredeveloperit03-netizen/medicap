import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Injectable({ providedIn: 'root' })
export class MethodWorkflowService {
  private readonly api = 'qc/method_workflow.php';

  constructor(private service: DataAccessService) {}

  submitForReview(specTestId: number | string): Observable<any> {
    return this.service.get(`${this.api}?type=submitForReview&id=${specTestId}`);
  }

  /** Send the entire MOA document (all prepared tests of a specification) to checking. */
  submitSpecForReview(specificationNo: string): Observable<any> {
    return this.service.get(`${this.api}?type=submitSpecForReview&specification_no=${encodeURIComponent(specificationNo)}`);
  }

  /** Full assembled MOA document: specification + common sections + every test's method. */
  getCompleteMethod(specificationNo: string): Observable<any> {
    return this.service.get(`qc/method.php?type=getCompleteMethod&specification_no=${encodeURIComponent(specificationNo)}`);
  }

  getReviewQueue(): Observable<any[]> {
    return this.service.loadList(`${this.api}?type=getReviewQueue`);
  }

  reviewAction(id: number | string, action: 'forward' | 'reject', remark = ''): Observable<any> {
    return this.service.post(
      `${this.api}?type=reviewAction&id=${id}&action=${action}`,
      JSON.stringify({ remark })
    );
  }

  getApprovalQueue(): Observable<any[]> {
    return this.service.loadList(`${this.api}?type=getApprovalQueue`);
  }

  approvalAction(id: number | string, action: 'approve' | 'reject', specificationNo: string, remark = ''): Observable<any> {
    return this.service.post(
      `${this.api}?type=approvalAction&id=${id}&action=${action}&specification_no=${encodeURIComponent(specificationNo)}`,
      JSON.stringify({ remark })
    );
  }

  getCorrectionQueue(): Observable<any[]> {
    return this.service.loadList(`${this.api}?type=getCorrectionQueue`);
  }

  /** Per-test queue (legacy methods/review module). */
  getTestReviewQueue(): Observable<any[]> {
    return this.service.loadList(`${this.api}?type=getTestReviewQueue`);
  }

  getTestApprovalQueue(): Observable<any[]> {
    return this.service.loadList(`${this.api}?type=getTestApprovalQueue`);
  }

  specReviewAction(specificationNo: string, action: 'forward' | 'reject', remark = ''): Observable<any> {
    return this.service.post(
      `${this.api}?type=specReviewAction&specification_no=${encodeURIComponent(specificationNo)}&action=${action}`,
      JSON.stringify({ remark, specification_no: specificationNo })
    );
  }

  specApprovalAction(specificationNo: string, action: 'approve' | 'reject', remark = ''): Observable<any> {
    return this.service.post(
      `${this.api}?type=specApprovalAction&specification_no=${encodeURIComponent(specificationNo)}&action=${action}`,
      JSON.stringify({ remark, specification_no: specificationNo })
    );
  }

  testLineAction(id: number | string, stage: 'checking' | 'approval', action: 'accept' | 'reject', remark = ''): Observable<any> {
    return this.service.post(
      `${this.api}?type=testLineAction&id=${id}&stage=${stage}&action=${action}`,
      JSON.stringify({ remark })
    );
  }

  getMethodLog(): Observable<any[]> {
    return this.service.get(`${this.api}?type=getMethodLog`).pipe(map((r) => (Array.isArray(r) ? r : [])));
  }

  updateLogStatus(id: number | string, logStatus: string): Observable<any> {
    return this.service.get(`${this.api}?type=updateLogStatus&id=${id}&log_status=${encodeURIComponent(logStatus)}`);
  }

  getRevisionTab(): Observable<any[]> {
    return this.service.get(`${this.api}?type=getRevisionTab`).pipe(map((r) => (Array.isArray(r) ? r : [])));
  }

  submitRevisionRequest(payload: Record<string, unknown>): Observable<any> {
    return this.service.post(`${this.api}?type=submitRevisionRequest`, JSON.stringify(payload));
  }

  getQaRevisionRequests(status = 'pending_qa'): Observable<any[]> {
    return this.service.get(`${this.api}?type=getQaRevisionRequests&status=${encodeURIComponent(status)}`).pipe(
      map((r) => (Array.isArray(r) ? r : []))
    );
  }

  qaRevisionAction(id: number | string, action: 'approve' | 'reject', payload: Record<string, unknown> = {}): Observable<any> {
    return this.service.post(`${this.api}?type=qaRevisionAction&id=${id}&action=${action}`, JSON.stringify(payload));
  }

  linkChangeControl(revisionRequestId: number | string, ccNo: string, ccId: number, editLink = ''): Observable<any> {
    return this.service.post(
      `${this.api}?type=linkChangeControl&id=${revisionRequestId}`,
      JSON.stringify({ revision_request_id: revisionRequestId, cc_no: ccNo, cc_id: ccId, method_edit_link: editLink })
    );
  }

  logStatusLabel(status: string): string {
    const mapLabels: Record<string, string> = {
      active: 'Active',
      inactive: 'In-Active',
      obsolete: 'Obsolete',
      under_revision: 'Under Revision',
    };
    return mapLabels[String(status || '').toLowerCase()] || status || 'Active';
  }
}
