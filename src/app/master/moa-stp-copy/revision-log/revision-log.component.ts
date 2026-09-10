import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-moa-stp-revision-log',
  templateUrl: './revision-log.component.html',
  styleUrls: ['./revision-log.component.css'],
})
export class RevisionLogComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  loading = false;
  searchQuery = '';
  results: any[] = [];
  revisionRequestByDoc: { [key: string]: any } = {};
  changeControlByDoc: { [key: string]: any } = {};
  draftByDoc: { [key: string]: any } = {};
  revisionModalOpen = false;
  selectedDoc: any = null;
  revisionForm: any = { reason: '', remarks: '' };
  draftModalOpen = false;
  draftLoading = false;
  draftSaving = false;
  draftForm: any = {};
  draftSubtests: any[] = [];
  draftReadOnly = false;

  ngOnInit(): void {
    this.loadLogs();
  }

  private getDocNo(row: any): string {
    const d = String(row?.test_method_no || '').trim();
    if (d) {
      return d;
    }
    return 'TEST-' + String(row?.id || '');
  }

  private loadLogs(): void {
    this.loading = true;
    this.service.get('master/moa_stp_copy.php?type=getMoaStpCombinedLogs').subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loadRevisionRequestStatuses();
        this.loadChangeControlStatuses();
        this.loadDraftStatuses();
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      },
    });
  }

  private loadRevisionRequestStatuses(): void {
    const docNos = this.results.map((x: any) => this.getDocNo(x)).filter((x: string) => x.length > 0);
    if (docNos.length === 0) {
      this.revisionRequestByDoc = {};
      return;
    }
    this.service
      .get(
        'master/moa_stp_copy.php?type=getMoaStpRevisionRequestStatus&doc_nos=' +
          encodeURIComponent(docNos.join(','))
      )
      .subscribe({
        next: (response: any) => {
          this.revisionRequestByDoc = response && typeof response === 'object' ? response : {};
        },
        error: () => {
          this.revisionRequestByDoc = {};
        },
      });
  }

  private loadChangeControlStatuses(): void {
    const docNos = this.results.map((x: any) => this.getDocNo(x)).filter((x: string) => x.length > 0);
    if (docNos.length === 0) {
      this.changeControlByDoc = {};
      return;
    }
    this.service
      .get(
        'master/moa_stp_copy.php?type=getChangeControlStatusForMoaStp&doc_nos=' +
          encodeURIComponent(docNos.join(','))
      )
      .subscribe({
        next: (response: any) => {
          this.changeControlByDoc = response && typeof response === 'object' ? response : {};
        },
        error: () => {
          this.changeControlByDoc = {};
        },
      });
  }

  private loadDraftStatuses(): void {
    const docNos = this.results.map((x: any) => this.getDocNo(x)).filter((x: string) => x.length > 0);
    if (docNos.length === 0) {
      this.draftByDoc = {};
      return;
    }
    this.service
      .get(
        'master/moa_stp_copy.php?type=getMoaStpDraftStatusForTests&doc_nos=' +
          encodeURIComponent(docNos.join(','))
      )
      .subscribe({
        next: (response: any) => {
          this.draftByDoc = response && typeof response === 'object' ? response : {};
        },
        error: () => {
          this.draftByDoc = {};
        },
      });
  }

  get filteredLogs(): any[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.results;
    }
    return this.results.filter((row: any) => {
      return Object.keys(row || {}).some((key) => {
        const value = row[key];
        return value != null && String(value).toLowerCase().includes(q);
      });
    });
  }

  getEffectiveDate(row: any): string {
    return row?.effective_date || row?.approve_date || row?.entry_date || '';
  }

  getNextReviewDate(row: any): string {
    if (row?.review_date) {
      return row.review_date;
    }
    const eff = this.parseDate(this.getEffectiveDate(row));
    if (!eff) {
      return '';
    }
    const d = new Date(eff);
    d.setDate(d.getDate() + 365);
    return d.toISOString().slice(0, 10);
  }

  private parseDate(value: any): Date | null {
    if (!value) {
      return null;
    }
    const date = new Date(value);
    if (isNaN(date.getTime())) {
      return null;
    }
    date.setHours(0, 0, 0, 0);
    return date;
  }

  private getDaysToReview(row: any): number | null {
    const nextReview = this.parseDate(this.getNextReviewDate(row));
    if (!nextReview) {
      return null;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffMs = nextReview.getTime() - today.getTime();
    return Math.floor(diffMs / (1000 * 60 * 60 * 24));
  }

  getRevisionButtonLabel(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'Approved';
    }
    if (this.isChangeControlRaised(row)) {
      return 'Change Control Raised';
    }
    const state = this.getRevisionRequestState(row);
    if (state === 'Accepted') {
      return 'Raise Change Control';
    }
    if (state === 'Pending') {
      return 'Request Pending';
    }
    const days = this.getDaysToReview(row);
    if (days == null) {
      return 'Revision Request';
    }
    if (days <= 15) {
      return 'Revision Due';
    }
    return 'Revision Request';
  }

  getRevisionButtonClass(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'btn-neutral bw-inactive';
    }
    if (this.isChangeControlRaised(row)) {
      return 'btn-primary';
    }
    const state = this.getRevisionRequestState(row);
    if (state === 'Accepted') {
      return 'btn-success';
    }
    if (state === 'Pending') {
      return 'btn-info';
    }
    const days = this.getDaysToReview(row);
    if (days == null) {
      return 'btn-primary';
    }
    if (days < 0) {
      return 'btn-danger revision-blink';
    }
    if (days <= 15) {
      return 'btn-warning';
    }
    return 'btn-primary';
  }

  isRevisionButtonDisabled(row: any): boolean {
    return this.getRevisionRequestState(row) === 'Pending' || this.isChangeControlApproved(row);
  }

  onRevisionAction(row: any): void {
    if (this.isChangeControlRaised(row)) {
      return;
    }
    const state = this.getRevisionRequestState(row);
    if (state === 'Accepted') {
      this.raiseChangeControl(row);
      return;
    }
    if (state === 'Pending') {
      return;
    }
    this.openRevisionModal(row);
  }

  getDisplayStatus(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'Approved';
    }
    const s = String(row?.status || '').toLowerCase();
    if (s === 'active' || s === 'approve' || s === 'approved') {
      const days = this.getDaysToReview(row);
      if (days != null && days <= 15) {
        return 'Revision Due';
      }
      return 'Not Due';
    }
    return String(row?.status || 'Pending');
  }

  isStatusBlinking(row: any): boolean {
    if (this.isChangeControlApproved(row)) {
      return false;
    }
    const days = this.getDaysToReview(row);
    const s = String(row?.status || '').toLowerCase();
    return (s === 'active' || s === 'approve' || s === 'approved') && days != null && days < 0;
  }

  getRevisionWorkflowStatus(row: any): string {
    if (!this.isRevisionDueState(row)) {
      return '-';
    }
    if (this.isChangeControlApproved(row)) {
      return 'Released';
    }
    if (this.getRevisionRequestState(row) === 'Accepted') {
      return 'Released Under Change Control';
    }
    return 'Blocked';
  }

  private getRevisionRequestState(row: any): string {
    const docNo = this.getDocNo(row);
    const req = this.revisionRequestByDoc[docNo];
    return String(req?.status || '').trim();
  }

  private getChangeControlState(row: any): string {
    const docNo = this.getDocNo(row);
    return String(this.changeControlByDoc[docNo]?.status || '').trim();
  }

  isChangeControlApproved(row: any): boolean {
    const state = this.getChangeControlState(row).toLowerCase();
    return ['approve', 'approved', 'complete', 'closed', 'close'].includes(state);
  }

  isChangeControlRejected(row: any): boolean {
    const state = this.getChangeControlState(row).toLowerCase();
    return state.includes('reject') || state.includes('back');
  }

  isChangeControlRaised(row: any): boolean {
    const state = this.getChangeControlState(row).toLowerCase();
    if (!state) {
      return false;
    }
    return !this.isChangeControlApproved(row) && !this.isChangeControlRejected(row);
  }

  getDisplayVersionNo(row: any): string {
    const current = String(row?.version_no || '01').trim();
    if (!this.isChangeControlApproved(row)) {
      return current;
    }
    const parsed = Number(current);
    if (!isNaN(parsed)) {
      const next = parsed + 1;
      return current.length >= 2 ? String(next).padStart(current.length, '0') : String(next);
    }
    return current;
  }

  private isRevisionDueState(row: any): boolean {
    const days = this.getDaysToReview(row);
    const s = String(row?.status || '').toLowerCase();
    return (s === 'active' || s === 'approve' || s === 'approved') && days != null && days <= 15;
  }

  openRevisionModal(row: any): void {
    this.selectedDoc = row;
    this.revisionForm = {
      reason: 'Deviation for the missed revision of MOA/STP and use of MOA/STP without revision',
      remarks: '',
    };
    this.revisionModalOpen = true;
  }

  closeRevisionModal(): void {
    this.revisionModalOpen = false;
    this.selectedDoc = null;
  }

  submitRevisionRequest(): void {
    if (!this.selectedDoc || !String(this.revisionForm?.reason || '').trim()) {
      return;
    }
    const payload = {
      doc_id: this.selectedDoc?.id || '',
      doc_no: this.getDocNo(this.selectedDoc),
      doc_name: this.selectedDoc?.test || '',
      reason: this.revisionForm.reason,
      remarks: this.revisionForm.remarks || '',
    };
    this.service
      .post('master/moa_stp_copy.php?type=saveMoaStpRevisionRequest', JSON.stringify(payload))
      .subscribe(
        (response: any) => {
          const status = String(response?.status || '').toLowerCase();
          if (status === 'success' || status === 'exists') {
            alertify.success(
              status === 'exists' ? 'Revision Request already pending.' : 'Revision Request sent to QA Revision.'
            );
            this.closeRevisionModal();
            this.loadRevisionRequestStatuses();
          } else {
            alertify.error('Failed to submit revision request');
          }
        },
        () => alertify.error('Failed to submit revision request')
      );
  }

  private raiseChangeControl(row: any): void {
    const docNo = this.getDocNo(row);
    const testName = String(row?.test || '').trim();
    const testType = String(row?.test_type || '').trim();
    const reason =
      this.revisionRequestByDoc[docNo]?.revisionComment ||
      'MOA/STP revision approved by QA. Change control initiated.';
    const proposed = 'Update MOA/STP document and revision metadata as per approved revision request.';

    this.router.navigate(['/qa/qms/change-control/new'], {
      queryParams: {
        prefill: '1',
        moa_doc_no: docNo,
        changeType: 'Method',
        titleOfcc: `MOA/STP Revision - ${docNo}`,
        justification: reason,
        proposed: `${proposed} MOA/STP: ${docNo}, Test: ${testName}, Type: ${testType || '-'}`,
      },
    });
  }

  isDraftButtonDisabled(row: any): boolean {
    return false;
  }

  hasDraft(row: any): boolean {
    const docNo = this.getDocNo(row);
    return !!this.draftByDoc[docNo];
  }

  getDraftButtonLabel(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'Final Draft';
    }
    if (this.isChangeControlRejected(row)) {
      return 'Draft MOA/STP';
    }
    if (this.isChangeControlRaised(row)) {
      return 'View Draft';
    }
    return this.hasDraft(row) ? 'View/Edit Draft' : 'Draft MOA/STP';
  }

  getDraftButtonClass(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'btn-success';
    }
    if (this.isChangeControlRejected(row)) {
      return 'btn-primary';
    }
    if (this.isChangeControlRaised(row)) {
      return 'btn-info';
    }
    return this.hasDraft(row) ? 'btn-info' : 'btn-primary';
  }

  openDraft(row: any, readOnly = false): void {
    if (this.isChangeControlApproved(row)) {
      this.submitFinalDraftForApproval(row);
      return;
    }
    const docNo = this.getDocNo(row);
    if (!docNo) {
      return;
    }
    this.draftReadOnly = readOnly;
    this.draftLoading = true;
    this.draftModalOpen = true;
    const loadDraft = readOnly || this.hasDraft(row);
    const endpoint = loadDraft
      ? 'master/moa_stp_copy.php?type=getMoaStpDraftByDocNo&doc_no=' + encodeURIComponent(docNo)
      : 'master/moa_stp_copy.php?type=getMoaStpWithDetailsForDraft&doc_no=' + encodeURIComponent(docNo);
    this.service.get(endpoint).subscribe(
      (response: any) => {
        const data = loadDraft ? response?.draft_data || {} : response || {};
        this.hydrateDraftForm(row, data);
        this.draftLoading = false;
      },
      () => {
        alertify.error('Failed to load draft data');
        this.draftLoading = false;
      }
    );
  }

  private submitFinalDraftForApproval(row: any): void {
    const docNo = this.getDocNo(row);
    if (!docNo) {
      return;
    }
    this.service
      .post(
        'master/moa_stp_copy.php?type=finalizeMoaStpDraftToPending',
        JSON.stringify({ doc_no: docNo })
      )
      .subscribe((response: any) => {
        const status = String(response?.status || '').toLowerCase();
        if (status === 'success') {
          alertify.success('Final Draft moved to MOA/STP approval workflow.');
          this.loadLogs();
          this.router.navigate(['/master/test/approval']);
        } else {
          alertify.error(response?.message || 'Failed to submit Final Draft');
        }
      }, () => {
        alertify.error('Failed to submit Final Draft');
      });
  }

  private hydrateDraftForm(row: any, data: any): void {
    this.draftForm = {
      source_doc_id: data?.id || row?.id || '',
      doc_no: data?.test_method_no || this.getDocNo(row),
      source_version_no: data?.version_no || row?.version_no || '01',
      draft_version_no: this.getDisplayVersionNo(row),
      draft_title: 'Draft MOA/STP Revision',
      test_type: data?.test_type || row?.test_type || '',
      test: data?.test || row?.test || '',
      effective_date: data?.effective_date || row?.effective_date || row?.approve_date || '',
      review_date: data?.review_date || row?.review_date || this.getNextReviewDate(row),
      status: data?.status || row?.status || '',
    };
    this.draftSubtests = Array.isArray(data?.subtests) ? [...data.subtests] : [];
  }

  addDraftSubtest(): void {
    this.draftSubtests.push({
      test_type: this.draftForm?.test_type || '',
      test: this.draftForm?.test || '',
      subtest: '',
    });
  }

  removeDraftSubtest(i: number): void {
    this.draftSubtests.splice(i, 1);
  }

  saveDraft(): void {
    if (this.draftReadOnly) {
      return;
    }
    if (!String(this.draftForm?.doc_no || '').trim()) {
      alertify.error('Document number is required');
      return;
    }
    this.draftSaving = true;
    const payload = {
      ...this.draftForm,
      subtests: this.draftSubtests,
    };
    this.service.post('master/moa_stp_copy.php?type=saveMoaStpDraft', JSON.stringify(payload)).subscribe(
      (response: any) => {
        this.draftSaving = false;
        if (String(response?.status || '').toLowerCase() === 'success') {
          alertify.success('Draft MOA/STP saved');
          this.loadDraftStatuses();
        } else {
          alertify.error('Failed to save draft');
        }
      },
      () => {
        this.draftSaving = false;
        alertify.error('Failed to save draft');
      }
    );
  }

  closeDraftModal(): void {
    this.draftModalOpen = false;
    this.draftForm = {};
    this.draftSubtests = [];
    this.draftReadOnly = false;
  }
}

