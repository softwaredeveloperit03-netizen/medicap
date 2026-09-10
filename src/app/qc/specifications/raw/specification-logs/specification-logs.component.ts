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
  selector: 'app-specification-logs',
  templateUrl: './specification-logs.component.html',
  styleUrls: ['./specification-logs.component.css']
})
export class SpecificationLogsComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  loading = false;
  searchQuery = '';
  activeFilter = 'raw';
  results: any[] = [];
  revisionRequestBySpec: { [key: string]: any } = {};
  revisionModalOpen = false;
  selectedSpec: any = null;
  viewModalOpen = false;
  selectedApprovedSpec: any = null;
  revisionForm: any = { reason: '', remarks: '' };

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

  downloadSpecificationLog(): void {
    const selected = this.filters.find((x) => x.id === this.activeFilter);
    const type = selected ? selected.apiType : 'Raw Material';
    this.service.open(
      'qc/specification/raw.php?type=downloadCombinedSpecificationLogs&log_type=' +
        encodeURIComponent(type)
    );
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
    this.service
      .get(
        'qc/specification/raw.php?type=getSpecificationRevisionRequestStatus&spec_nos=' +
          encodeURIComponent(specNos.join(','))
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

  private parseDate(value: any): Date | null {
    if (!value) {
      return null;
    }
    const d = new Date(value);
    if (!isNaN(d.getTime())) {
      return d;
    }
    if (typeof value === 'string') {
      const parts = value.split('-');
      if (parts.length === 3) {
        const y = Number(parts[0]);
        const m = Number(parts[1]) - 1;
        const day = Number(parts[2]);
        const parsed = new Date(y, m, day);
        if (!isNaN(parsed.getTime())) {
          return parsed;
        }
      }
    }
    return null;
  }

  getNextReviewDate(row: any): Date | null {
    return this.parseDate(row?.review_date);
  }

  getDaysToReview(row: any): number | null {
    const reviewDate = this.getNextReviewDate(row);
    if (!reviewDate) {
      return null;
    }
    const today = new Date();
    const startToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const startReview = new Date(reviewDate.getFullYear(), reviewDate.getMonth(), reviewDate.getDate());
    const diff = startReview.getTime() - startToday.getTime();
    return Math.floor(diff / (1000 * 60 * 60 * 24));
  }

  getDisplayStatus(row: any): string {
    const s = String(row?.status || '').toLowerCase();
    if (s === 'approved' || s === 'approve') {
      const days = this.getDaysToReview(row);
      if (days != null && days <= 15) {
        return 'Revision Due';
      }
      return 'Approved';
    }
    if (s === 'checking' || s === 'pending') {
      return 'Checking';
    }
    if (s === 'pending_approval' || s === 'checked') {
      return 'Pending Approval';
    }
    if (s === 'rejected' || s === 'reject') {
      return 'Rejected';
    }
    return String(row?.status || '-');
  }

  isStatusBlinking(row: any): boolean {
    const s = String(row?.status || '').toLowerCase();
    const days = this.getDaysToReview(row);
    return (s === 'approved' || s === 'approve') && days != null && days < 0;
  }

  private getRevisionRequestState(row: any): string {
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      return '';
    }
    const req = this.revisionRequestBySpec[specNo];
    return String(req?.status || '').trim();
  }

  getRevisionWorkflowStatus(row: any): string {
    if (!this.isRevisionDueState(row)) {
      return '-';
    }
    return this.getRevisionRequestState(row) === 'Accepted' ? 'Released Under Change Control' : 'Blocked';
  }

  private isRevisionDueState(row: any): boolean {
    const days = this.getDaysToReview(row);
    const s = String(row?.status || '').toLowerCase();
    return (s === 'approved' || s === 'approve') && days != null && days <= 15;
  }

  getRevisionButtonLabel(row: any): string {
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

  canViewApprovedSpec(row: any): boolean {
    const s = String(row?.status || '').toLowerCase();
    return s === 'approved' || s === 'approve';
  }

  shouldShowViewSpecificationColumn(): boolean {
    return this.activeFilter === 'raw' || this.activeFilter === 'packing' || this.activeFilter === 'finished';
  }

  openApprovedSpec(row: any): void {
    if (!this.canViewApprovedSpec(row)) {
      return;
    }
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      alertify.error('Specification number not found');
      return;
    }
    const specId = row?.id != null ? String(row.id).trim() : '';
    let url =
      'qc/specification/raw.php?type=getSpecificationWithDetailsForDraft&specification_no=' +
      encodeURIComponent(specNo);
    if (specId) {
      url += '&spec_id=' + encodeURIComponent(specId);
    }
    this.service
      .get(url)
      .subscribe({
        next: (response: any) => {
          const data = response && typeof response === 'object' ? response : null;
          if (!data || !data.specification_no) {
            alertify.error('Approved specification details not found');
            return;
          }
          this.selectedApprovedSpec = {
            ...data,
            spectTests: Array.isArray(data.spectTests) ? data.spectTests : [],
            revisionList: Array.isArray(data.revisionList) ? data.revisionList : [],
          };
          this.viewModalOpen = true;
        },
        error: () => alertify.error('Unable to load approved specification')
      });
  }

  closeApprovedSpecModal(): void {
    this.viewModalOpen = false;
    this.selectedApprovedSpec = null;
  }

  private approvedSpecMaterialType(): string {
    const specType = String(this.selectedApprovedSpec?.spec_type || '').toLowerCase();
    if (specType.includes('packing')) {
      return 'Packing Material';
    }
    if (specType.includes('semi finished') || specType.includes('semi-finished')) {
      return 'Semi Finished Goods';
    }
    if (specType.includes('finish')) {
      return 'Finish Product';
    }
    return 'Raw Material';
  }

  isRawApprovedSpec(): boolean {
    return this.approvedSpecMaterialType() === 'Raw Material';
  }

  isRawOrPackingApprovedSpec(): boolean {
    const mt = this.approvedSpecMaterialType();
    return mt === 'Raw Material' || mt === 'Packing Material';
  }

  isFinishApprovedSpec(): boolean {
    const mt = this.approvedSpecMaterialType();
    return mt === 'Finish Product' || mt === 'Semi Finished Goods';
  }

  showApprovedSpecTestSampleQty(): boolean {
    return String(this.selectedApprovedSpec?.sampling_plan || '') !== 'Fixed';
  }

  downloadApprovedSpecPdf(): void {
    const specNo = String(this.selectedApprovedSpec?.specification_no || '').trim();
    if (!specNo) {
      alertify.error('Specification number not found');
      return;
    }
    this.service.open(
      'qc/specification/raw.php?type=downloadApprovedSpecificationForm&specification_no=' +
        encodeURIComponent(specNo)
    );
  }

  getRevisionButtonClass(row: any): string {
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
    return this.getRevisionRequestState(row) === 'Pending';
  }

  onRevisionAction(row: any): void {
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
        changeType: 'Specification',
        titleOfcc: `Specification Revision - ${specNo}`,
        justification: reason,
        proposed:
          `${proposed} Specification: ${specNo}, Type: ${specType}, Item: ${materialName} (${materialCode || '-'})`
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
}
