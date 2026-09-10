import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

interface LogFilter {
  id: string;
  label: string;
  apiType: string;
}

@Component({
  selector: 'app-specification-revision-log',
  templateUrl: './specification-revision-log.component.html',
  styleUrls: ['./specification-revision-log.component.css']
})
export class SpecificationRevisionLogComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  loading = false;
  searchQuery = '';
  activeFilter = 'raw';
  results: any[] = [];
  revisionRequestBySpec: { [key: string]: any } = {};
  changeControlBySpec: { [key: string]: any } = {};
  draftBySpec: { [key: string]: any } = {};
  revisionModalOpen = false;
  selectedSpec: any = null;
  revisionForm: any = { reason: '', remarks: '' };
  draftModalOpen = false;
  draftLoading = false;
  draftSaving = false;
  draftForm: any = {};
  draftTests: any[] = [];
  draftRevisions: any[] = [];
  draftReadOnly = false;

  /** Revision Request is required when next review date is within this many days. */
  private readonly revisionDueWindowDays = 10;

  readonly filters: LogFilter[] = [
    { id: 'raw', label: 'Raw Material', apiType: 'Raw Material' },
    { id: 'packing', label: 'Packing Material', apiType: 'Packing Material' },
    { id: 'finished', label: 'Finished Product', apiType: 'Finish Product' },
    { id: 'inprocess', label: 'Inprocess', apiType: 'Inprocess' },
    { id: 'water', label: 'Water', apiType: 'Water Specification' }
  ];

  ngOnInit(): void {
    this.loadLogs();
  }

  setFilter(filterId: string): void {
    if (this.activeFilter === filterId) {
      return;
    }
    this.activeFilter = filterId;
    this.loadLogs();
  }

  private loadLogs(): void {
    const selected = this.filters.find((x) => x.id === this.activeFilter);
    const type = selected ? selected.apiType : 'Raw Material';
    this.loading = true;
    this.service
      .get(
        'qc/specification/raw.php?type=getCombinedSpecificationLogs&log_type=' +
          encodeURIComponent(type)
      )
      .subscribe({
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
        }
      });
  }

  private loadRevisionRequestStatuses(): void {
    const specNos = this.results
      .map((x: any) => String(x?.specification_no || '').trim())
      .filter((x: string) => x.length > 0);
    if (specNos.length === 0) {
      this.revisionRequestBySpec = {};
      return;
    }
    const joined = specNos.join(',');
    this.service
      .get(
        'qc/specification/raw.php?type=getSpecificationRevisionRequestStatus&spec_nos=' +
          encodeURIComponent(joined)
      )
      .subscribe({
        next: (response: any) => {
          this.revisionRequestBySpec = response && typeof response === 'object' ? response : {};
        },
        error: () => {
          this.revisionRequestBySpec = {};
        }
      });
  }

  private loadChangeControlStatuses(): void {
    const specNos = this.results
      .map((x: any) => String(x?.specification_no || '').trim())
      .filter((x: string) => x.length > 0);
    if (specNos.length === 0) {
      this.changeControlBySpec = {};
      return;
    }
    this.service
      .get(
        'qc/specification/raw.php?type=getChangeControlStatusForSpecifications&spec_nos=' +
          encodeURIComponent(specNos.join(','))
      )
      .subscribe({
        next: (response: any) => {
          this.changeControlBySpec = response && typeof response === 'object' ? response : {};
        },
        error: () => {
          this.changeControlBySpec = {};
        }
      });
  }

  private loadDraftStatuses(): void {
    const specNos = this.results
      .map((x: any) => String(x?.specification_no || '').trim())
      .filter((x: string) => x.length > 0);
    if (specNos.length === 0) {
      this.draftBySpec = {};
      return;
    }
    this.service
      .get(
        'qc/specification/raw.php?type=getSpecificationDraftStatusForSpecifications&spec_nos=' +
          encodeURIComponent(specNos.join(','))
      )
      .subscribe({
        next: (response: any) => {
          this.draftBySpec = response && typeof response === 'object' ? response : {};
        },
        error: () => {
          this.draftBySpec = {};
        }
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
    return row?.effective_date || row?.approve_date || '';
  }

  getNextReviewDate(row: any): string {
    return row?.review_date || '';
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
    if (days <= this.revisionDueWindowDays) {
      return 'Revision Due';
    }
    return 'Revision Request';
  }

  getRevisionButtonClass(row: any): string {
    if (this.isRevisionButtonDisabled(row)) {
      return 'btn-neutral bw-inactive';
    }
    if (this.isChangeControlRaised(row)) {
      return 'btn-primary';
    }
    const state = this.getRevisionRequestState(row);
    if (state === 'Accepted') {
      return 'btn-success';
    }
    const days = this.getDaysToReview(row);
    if (days != null && days < 0) {
      return 'btn-danger revision-blink';
    }
    if (this.isRevisionDueState(row)) {
      return 'btn-warning';
    }
    return 'btn-primary';
  }

  isRevisionButtonDisabled(row: any): boolean {
    if (this.isChangeControlApproved(row) || this.isChangeControlRaised(row)) {
      return true;
    }
    const state = this.getRevisionRequestState(row);
    if (state === 'Pending') {
      return true;
    }
    if (state === 'Accepted') {
      return false;
    }
    return !this.isRevisionDueState(row);
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
    if (s === 'approved' || s === 'approve') {
      const days = this.getDaysToReview(row);
      if (days != null && days <= this.revisionDueWindowDays) {
        return 'Revision Due';
      }
    }
    return String(row?.status || '');
  }

  isStatusBlinking(row: any): boolean {
    if (this.isChangeControlApproved(row)) {
      return false;
    }
    const days = this.getDaysToReview(row);
    const s = String(row?.status || '').toLowerCase();
    return (s === 'approved' || s === 'approve') && days != null && days < 0;
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
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      return '';
    }
    const req = this.revisionRequestBySpec[specNo];
    return String(req?.status || '').trim();
  }

  private getChangeControlState(row: any): string {
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      return '';
    }
    return String(this.changeControlBySpec[specNo]?.status || '').trim();
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
    const current = String(row?.version_no || '').trim();
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
    return (s === 'approved' || s === 'approve') && days != null && days <= this.revisionDueWindowDays;
  }

  openRevisionModal(row: any): void {
    this.selectedSpec = row;
    this.revisionForm = {
      reason:
        'Deviation for the missed revision of the Specification and use of Specfication without revision',
      remarks: ''
    };
    this.revisionModalOpen = true;
  }

  closeRevisionModal(): void {
    this.revisionModalOpen = false;
    this.selectedSpec = null;
  }

  submitRevisionRequest(): void {
    if (!this.selectedSpec || !String(this.revisionForm?.reason || '').trim()) {
      return;
    }
    const payload = {
      specification_id: this.selectedSpec?.id || '',
      specification_no: this.selectedSpec?.specification_no || '',
      specification_name: this.selectedSpec?.material_name || '',
      reason: this.revisionForm.reason,
      remarks: this.revisionForm.remarks || ''
    };
    this.service
      .post(
        'qc/specification/raw.php?type=saveSpecificationRevisionRequest',
        JSON.stringify(payload)
      )
      .subscribe((response: any) => {
        const status = String(response?.status || '').toLowerCase();
        if (status === 'success' || status === 'exists') {
          alertify.success(status === 'exists' ? 'Revision Request already pending.' : 'Revision Request sent to QA Revision.');
          this.closeRevisionModal();
          this.loadRevisionRequestStatuses();
        } else {
          alertify.error('Failed to submit revision request');
        }
      }, () => alertify.error('Failed to submit revision request'));
  }

  private raiseChangeControl(row: any): void {
    const specNo = String(row?.specification_no || '').trim();
    const materialName = String(row?.material_name || '').trim();
    const materialCode = String(row?.material_code || '').trim();
    const specType = String(row?.spec_type || '').trim();
    const reason =
      this.revisionRequestBySpec[specNo]?.revisionComment ||
      'Specification revision approved by QA. Change control initiated.';
    const proposed =
      'Update specification document, revision metadata, and effective/review dates as per approved revision request.';

    this.router.navigate(['/qa/qms/change-control/new'], {
      queryParams: {
        prefill: '1',
        spec_no: specNo,
        changeType: 'Specification',
        titleOfcc: `Specification Revision - ${specNo}`,
        justification: reason,
        proposed:
          `${proposed} Specification: ${specNo}, Type: ${specType}, Item: ${materialName} (${materialCode || '-'})`
      }
    });
  }

  hasDraft(row: any): boolean {
    const specNo = String(row?.specification_no || '').trim();
    return !!this.draftBySpec[specNo];
  }

  isDraftButtonDisabled(row: any): boolean {
    if (this.isChangeControlApproved(row) || this.isChangeControlRaised(row)) {
      return false;
    }
    if (this.isChangeControlRejected(row)) {
      return false;
    }
    const state = this.getRevisionRequestState(row);
    if (state === 'Pending' || state === 'Accepted') {
      return false;
    }
    return true;
  }

  getDraftButtonLabel(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'Final Draft';
    }
    if (this.isChangeControlRejected(row)) {
      return 'Draft Specification';
    }
    if (this.isChangeControlRaised(row)) {
      return 'View Draft';
    }
    if (this.isDraftButtonDisabled(row)) {
      return 'Draft Specification';
    }
    return this.hasDraft(row) ? 'View/Edit Draft' : 'Draft Specification';
  }

  getDraftButtonClass(row: any): string {
    if (this.isDraftButtonDisabled(row)) {
      return 'btn-neutral bw-inactive';
    }
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

  openDraftSpecification(row: any, readOnly = false): void {
    if (this.isDraftButtonDisabled(row)) {
      return;
    }
    if (this.isChangeControlApproved(row)) {
      this.submitFinalDraftForApproval(row);
      return;
    }
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      return;
    }
    this.draftReadOnly = readOnly || this.isChangeControlRaised(row);
    this.draftLoading = true;
    this.draftModalOpen = true;
    const loadDraft = this.draftReadOnly || this.hasDraft(row);
    const endpoint = loadDraft
      ? 'qc/specification/raw.php?type=getSpecificationDraftBySpecNo&specification_no=' + encodeURIComponent(specNo)
      : 'qc/specification/raw.php?type=getSpecificationWithDetailsForDraft&specification_no=' + encodeURIComponent(specNo);
    this.service.get(endpoint).subscribe({
      next: (response: any) => {
        const data = loadDraft ? (response?.draft_data || {}) : (response || {});
        this.hydrateDraftForm(row, data);
        this.draftLoading = false;
      },
      error: () => {
        alertify.error('Failed to load draft data');
        this.draftLoading = false;
      },
    });
  }

  private submitFinalDraftForApproval(row: any): void {
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      return;
    }
    this.service
      .post(
        'qc/specification/raw.php?type=finalizeSpecificationDraftToPending',
        JSON.stringify({ specification_no: specNo })
      )
      .subscribe((response: any) => {
        const status = String(response?.status || '').toLowerCase();
        if (status === 'success') {
          alertify.success('Final Draft moved to specification approval workflow.');
          this.loadLogs();
          this.router.navigate(['/master/specification/raw/checking']);
        } else {
          alertify.error(response?.message || 'Failed to submit Final Draft');
        }
      }, () => {
        alertify.error('Failed to submit Final Draft');
      });
  }

  private hydrateDraftForm(row: any, data: any): void {
    this.draftForm = {
      source_spec_id: data?.id || row?.id || '',
      specification_no: data?.specification_no || row?.specification_no || '',
      source_version_no: data?.version_no || row?.version_no || '',
      draft_version_no: data?.draft_version_no || this.getDisplayVersionNo(row),
      draft_title: data?.draft_title || 'Draft Specification Revision',
      spec_type: data?.spec_type || row?.spec_type || '',
      material_code: data?.material_code || row?.material_code || '',
      material_name: data?.material_name || row?.material_name || '',
      supersede_no: data?.supersede_no || row?.supersede_no || '',
      review_date: this.toDateInputValue(data?.review_date || row?.review_date),
      effective_date: this.toDateInputValue(data?.effective_date || row?.effective_date || row?.approve_date),
      retest_period: data?.retest_period || '',
      sampling_plan: data?.sampling_plan || '',
      sample_qty: data?.sample_qty || '',
      control_sample: data?.control_sample || '',
      additional_sample: data?.additional_sample || '',
      totalsample_qty: data?.totalsample_qty || '',
      storage_condition: data?.storage_condition || data?.storage || '',
      samplingDetails: data?.samplingDetails || '',
      hazardAndPrecautions: data?.hazardAndPrecautions || data?.safety_precaution || '',
      note: data?.note || '',
    };
    const tests = Array.isArray(data?.spectTests)
      ? data.spectTests
      : Array.isArray(data?.spec_tests)
        ? data.spec_tests
        : [];
    this.draftTests = tests.map((test: any) => this.normalizeDraftTestRow(test));
    const revisions = Array.isArray(data?.revisionList) ? data.revisionList : [];
    this.draftRevisions = revisions.map((rev: any) => this.normalizeDraftRevisionRow(rev));
  }

  private toDateInputValue(value: any): string {
    if (!value) {
      return '';
    }
    const raw = String(value).trim();
    if (raw === '0000-00-00' || raw.startsWith('0000-00-00')) {
      return '';
    }
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
      return raw.slice(0, 10);
    }
    const parsed = new Date(raw);
    if (isNaN(parsed.getTime())) {
      return '';
    }
    return parsed.toISOString().slice(0, 10);
  }

  private normalizeDraftTestRow(row: any): any {
    return {
      test_type: row?.test_type || '',
      test: row?.test || '',
      subtest: row?.subtest || 'NA',
      limit_type: row?.limit_type || '',
      limits: row?.limits || row?.description || row?.limit || '',
      test_method_no: row?.test_method_no || row?.method || '',
    };
  }

  private normalizeDraftRevisionRow(row: any): any {
    return {
      version_no: row?.version_no || '',
      change_mode: row?.change_mode || '',
      reason: row?.reason || '',
      effective_date: this.toDateInputValue(row?.effective_date),
    };
  }

  addDraftTest(): void {
    this.draftTests.push({
      test_type: '',
      test: '',
      subtest: '',
      limit_type: '',
      limits: '',
      test_method_no: ''
    });
  }

  removeDraftTest(i: number): void {
    this.draftTests.splice(i, 1);
  }

  addDraftRevision(): void {
    this.draftRevisions.push({
      version_no: this.draftForm?.draft_version_no || '',
      change_mode: '',
      reason: '',
      effective_date: this.draftForm?.effective_date || ''
    });
  }

  removeDraftRevision(i: number): void {
    this.draftRevisions.splice(i, 1);
  }

  saveDraftSpecification(): void {
    if (this.draftReadOnly) {
      return;
    }
    if (!String(this.draftForm?.specification_no || '').trim()) {
      alertify.error('Specification number is required');
      return;
    }
    this.draftSaving = true;
    const payload = {
      ...this.draftForm,
      spectTests: this.draftTests,
      revisionList: this.draftRevisions,
    };
    this.service
      .postJson('qc/specification/raw.php?type=saveSpecificationDraft', JSON.stringify(payload))
      .subscribe({
        next: (response: any) => {
          this.draftSaving = false;
          const status = String(response?.status || '').toLowerCase();
          if (status === 'success') {
            alertify.success('Draft specification saved');
            this.loadDraftStatuses();
          } else {
            alertify.error(response?.message || response?.status || 'Failed to save draft');
          }
        },
        error: () => {
          this.draftSaving = false;
          alertify.error('Failed to save draft');
        },
      });
  }

  closeDraftModal(): void {
    this.draftModalOpen = false;
    this.draftForm = {};
    this.draftTests = [];
    this.draftRevisions = [];
    this.draftReadOnly = false;
  }
}
