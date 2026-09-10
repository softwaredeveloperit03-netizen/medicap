import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-fg-sampling-checklist-master-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  activeTab: 'new' | 'priority' | 'checking' | 'approval' | 'log' | 'revision' | 'obsolete' = 'new';
  loading = false;
  searchQuery = '';

  ischecker = 'No';
  isapprover = 'No';

  checklistLog: any[] = [];
  selectedChecklistForPriority = '';
  priorityRows: any[] = [];
  prioritySaving = false;
  viewModalOpen = false;
  selectedViewRow: any = null;

  revisionRequestByChecklist: { [key: string]: any } = {};
  changeControlByChecklist: { [key: string]: any } = {};
  revisionModalOpen = false;
  selectedRevisionRow: any = null;
  revisionForm: any = { reason: '', remarks: '' };

  formData: any = {
    sampling_type: 'Raw Material Sampling',
    checklist_title: 'Pharma Raw Material Sampling Checklist',
    effective_date: '',
    next_review_date: '',
    checkpoints: [],
  };

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.addCheckpoint();
    this.loadChecklistLog();
    this.getRights();
  }

  private getRights(): void {
    const emp = encodeURIComponent(localStorage.getItem('emp_id') || '');
    const dep = encodeURIComponent(localStorage.getItem('department') || '');
    this.service.get('hr/employee.php?type=getrights&emp_id=' + emp + '&dep_name=' + dep).subscribe((response: any) => {
      if (Array.isArray(response) && response[0]) {
        this.ischecker = response[0].ischecker || 'No';
        this.isapprover = response[0].isapprover || 'No';
      }
    });
  }

  addCheckpoint(): void {
    this.formData.checkpoints.push({
      checkpoint: '',
      checkpoint_description: '',
      checkpoint_particular: 'Remark',
    });
  }

  removeCheckpoint(i: number): void {
    this.formData.checkpoints.splice(i, 1);
  }

  saveChecklist(form: any): void {
    if (!form.valid || !Array.isArray(this.formData.checkpoints) || this.formData.checkpoints.length === 0) {
      alertify.error('Please fill checklist details and add checkpoints');
      return;
    }

    const invalidPoint = this.formData.checkpoints.some((x: any) => !String(x?.checkpoint || '').trim());
    if (invalidPoint) {
      alertify.error('Checkpoint is required for all rows');
      return;
    }

    this.service
      .post('master/fg_sampling_checklist.php?type=saveChecklistMaster', JSON.stringify(this.formData))
      .subscribe((response: any) => {
        if (String(response?.status || '').toLowerCase() === 'success') {
          alertify.success('Checklist saved in pending state for approval matrix');
          this.formData = {
            sampling_type: 'Raw Material Sampling',
            checklist_title: 'Pharma Raw Material Sampling Checklist',
            effective_date: '',
            next_review_date: '',
            checkpoints: [],
          };
          this.addCheckpoint();
          this.activeTab = 'log';
          this.loadChecklistLog();
        } else {
          alertify.error(response?.message || 'Failed to save checklist');
        }
      });
  }

  loadChecklistLog(): void {
    this.loading = true;
    this.service.get('master/fg_sampling_checklist.php?type=getChecklistLog').subscribe(
      (response: any) => {
        this.checklistLog = Array.isArray(response) ? response : [];
        this.loadRevisionStatuses();
        this.loadChangeControlStatuses();
        this.loading = false;
        this.reloadPriorityRowsIfNeeded();
      },
      () => {
        this.checklistLog = [];
        this.loading = false;
      }
    );
  }

  get filteredLog(): any[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.checklistLog;
    }
    return this.checklistLog.filter((row: any) =>
      Object.keys(row || {}).some((key) => {
        const v = row[key];
        return v != null && String(v).toLowerCase().includes(q);
      })
    );
  }

  get checkingRows(): any[] {
    return this.filteredLog.filter((x: any) => String(x?.status || '').toLowerCase() === 'pending');
  }

  get approvalRows(): any[] {
    return this.filteredLog.filter((x: any) => String(x?.status || '').toLowerCase() === 'checked');
  }

  get obsoleteRows(): any[] {
    return this.filteredLog.filter((x: any) => {
      const s = String(x?.status || '').toLowerCase();
      return s === 'superseded' || s === 'obsolete';
    });
  }

  private reloadPriorityRowsIfNeeded(): void {
    if (this.selectedChecklistForPriority) {
      this.loadPriorityRows(this.selectedChecklistForPriority);
    }
  }

  get approvedRowsForPriority(): any[] {
    return this.checklistLog.filter((x: any) => String(x?.status || '').toLowerCase() === 'approved');
  }

  loadPriorityRows(checklistNo: string): void {
    this.selectedChecklistForPriority = checklistNo;
    if (!checklistNo) {
      this.priorityRows = [];
      return;
    }
    this.service
      .get(
        'master/fg_sampling_checklist.php?type=getChecklistByNo&checklist_no=' +
          encodeURIComponent(checklistNo)
      )
      .subscribe((response: any) => {
        const row = response && typeof response === 'object' ? response : null;
        this.priorityRows = Array.isArray(row?.checkpoints) ? row.checkpoints : [];
        this.priorityRows.sort((a: any, b: any) => Number(a?.priority_no || 0) - Number(b?.priority_no || 0));
      });
  }

  savePriority(): void {
    if (!this.selectedChecklistForPriority || this.priorityRows.length === 0) {
      alertify.error('No checklist selected for priority');
      return;
    }
    const hasInvalid = this.priorityRows.some((x: any) => Number(x?.priority_no || 0) <= 0);
    if (hasInvalid) {
      alertify.error('Priority must be greater than 0');
      return;
    }
    this.prioritySaving = true;
    const payload = {
      checklist_no: this.selectedChecklistForPriority,
      priorities: this.priorityRows.map((x: any) => ({
        id: x.id,
        priority_no: Number(x.priority_no || 0),
      })),
    };
    this.service
      .post('master/fg_sampling_checklist.php?type=updateCheckpointPriorities', JSON.stringify(payload))
      .subscribe((response: any) => {
        this.prioritySaving = false;
        if (String(response?.status || '').toLowerCase() === 'success') {
          alertify.success('Priority updated');
          this.loadChecklistLog();
        } else {
          alertify.error('Failed to update priority');
        }
      }, () => {
        this.prioritySaving = false;
        alertify.error('Failed to update priority');
      });
  }

  openView(row: any): void {
    this.selectedViewRow = row;
    this.viewModalOpen = true;
  }

  updateStatus(row: any, status: string): void {
    const payload = { id: row.id, status };
    this.service
      .post('master/fg_sampling_checklist.php?type=updateChecklistStatus', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (String(response?.status || '').toLowerCase() === 'success') {
          alertify.success('Status updated');
          this.loadChecklistLog();
        } else {
          alertify.error('Failed to update status');
        }
      });
  }

  isRevisionRowEligible(row: any): boolean {
    return String(row?.status || '').toLowerCase() === 'approved';
  }

  private parseDate(value: any): Date | null {
    if (!value) {
      return null;
    }
    const d = new Date(value);
    if (isNaN(d.getTime())) {
      return null;
    }
    d.setHours(0, 0, 0, 0);
    return d;
  }

  getDaysToReview(row: any): number | null {
    const nextReview = this.parseDate(row?.next_review_date);
    if (!nextReview) {
      return null;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.floor((nextReview.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
  }

  private getRevisionRequestState(row: any): string {
    const ck = String(row?.checklist_no || '').trim();
    return String(this.revisionRequestByChecklist[ck]?.status || '').trim();
  }

  private getChangeControlState(row: any): string {
    const ck = String(row?.checklist_no || '').trim();
    return String(this.changeControlByChecklist[ck]?.status || '').trim();
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
    const s = this.getChangeControlState(row).toLowerCase();
    return !!s && !this.isChangeControlApproved(row) && !this.isChangeControlRejected(row);
  }

  getRevisionButtonLabel(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'Create Revised Checklist';
    }
    if (this.isChangeControlRaised(row)) {
      return 'Change Control Raised';
    }
    const req = this.getRevisionRequestState(row);
    if (req === 'Accepted') {
      return 'Raise Change Control';
    }
    if (req === 'Pending') {
      return 'Request Pending';
    }
    const days = this.getDaysToReview(row);
    if (days != null && days <= 15) {
      return 'Revision Due';
    }
    return 'Revision Request';
  }

  getRevisionButtonClass(row: any): string {
    if (this.isChangeControlApproved(row)) {
      return 'btn-success';
    }
    if (this.isChangeControlRaised(row)) {
      return 'btn-primary';
    }
    const req = this.getRevisionRequestState(row);
    if (req === 'Accepted') {
      return 'btn-success';
    }
    if (req === 'Pending') {
      return 'btn-info';
    }
    const days = this.getDaysToReview(row);
    if (days != null && days < 0) {
      return 'btn-danger revision-blink';
    }
    if (days != null && days <= 15) {
      return 'btn-warning';
    }
    return 'btn-primary';
  }

  isRevisionButtonDisabled(row: any): boolean {
    return this.getRevisionRequestState(row) === 'Pending';
  }

  onRevisionAction(row: any): void {
    if (this.isChangeControlApproved(row)) {
      this.createRevisedChecklist(row);
      return;
    }
    if (this.isChangeControlRaised(row)) {
      return;
    }
    const req = this.getRevisionRequestState(row);
    if (req === 'Accepted') {
      this.raiseChangeControl(row);
      return;
    }
    if (req === 'Pending') {
      return;
    }
    this.openRevisionModal(row);
  }

  openRevisionModal(row: any): void {
    this.selectedRevisionRow = row;
    this.revisionForm = {
      reason: 'Revision request for FG Sampling Checklist due to review schedule.',
      remarks: '',
    };
    this.revisionModalOpen = true;
  }

  closeRevisionModal(): void {
    this.revisionModalOpen = false;
    this.selectedRevisionRow = null;
  }

  submitRevisionRequest(): void {
    if (!this.selectedRevisionRow || !String(this.revisionForm?.reason || '').trim()) {
      return;
    }
    const payload = {
      checklist_id: this.selectedRevisionRow?.id || '',
      checklist_no: this.selectedRevisionRow?.checklist_no || '',
      checklist_name: this.selectedRevisionRow?.checklist_title || 'FG Sampling Checklist',
      reason: this.revisionForm.reason,
      remarks: this.revisionForm.remarks || '',
    };
    this.service
      .post('master/fg_sampling_checklist.php?type=saveChecklistRevisionRequest', JSON.stringify(payload))
      .subscribe((response: any) => {
        const status = String(response?.status || '').toLowerCase();
        if (status === 'success' || status === 'exists') {
          alertify.success(status === 'exists' ? 'Revision request already pending.' : 'Revision request sent.');
          this.closeRevisionModal();
          this.loadRevisionStatuses();
        } else {
          alertify.error('Failed to submit revision request');
        }
      }, () => alertify.error('Failed to submit revision request'));
  }

  private raiseChangeControl(row: any): void {
    const checklistNo = String(row?.checklist_no || '').trim();
    const reason =
      this.revisionRequestByChecklist[checklistNo]?.revisionComment ||
      'FG Sampling Checklist revision approved by QA. Change control initiated.';
    this.router.navigate(['/qa/qms/change-control/new'], {
      queryParams: {
        prefill: '1',
        changeType: 'Document',
        titleOfcc: `FG Sampling Checklist Revision - ${checklistNo}`,
        justification: reason,
        proposed:
          `Update FG sampling checklist as per approved revision request. Checklist: ${checklistNo}.`,
      },
    });
  }

  private createRevisedChecklist(row: any): void {
    const checklistNo = String(row?.checklist_no || '').trim();
    this.service
      .post(
        'master/fg_sampling_checklist.php?type=createRevisedChecklistFromApprovedCc',
        JSON.stringify({ checklist_no: checklistNo })
      )
      .subscribe((response: any) => {
        const status = String(response?.status || '').toLowerCase();
        if (status === 'success') {
          alertify.success('Revised checklist created in pending state.');
          this.activeTab = 'log';
          this.loadChecklistLog();
        } else if (status === 'exists') {
          alertify.success('Revised checklist already exists in pending state.');
          this.activeTab = 'log';
          this.loadChecklistLog();
        } else {
          alertify.error(response?.message || 'Failed to create revised checklist');
        }
      }, () => alertify.error('Failed to create revised checklist'));
  }

  private loadRevisionStatuses(): void {
    const nos = this.checklistLog
      .map((x: any) => String(x?.checklist_no || '').trim())
      .filter((x: string) => !!x);
    if (nos.length === 0) {
      this.revisionRequestByChecklist = {};
      return;
    }
    this.service
      .get(
        'master/fg_sampling_checklist.php?type=getChecklistRevisionRequestStatus&checklist_nos=' +
          encodeURIComponent(nos.join(','))
      )
      .subscribe((response: any) => {
        this.revisionRequestByChecklist = response && typeof response === 'object' ? response : {};
      }, () => {
        this.revisionRequestByChecklist = {};
      });
  }

  private loadChangeControlStatuses(): void {
    const nos = this.checklistLog
      .map((x: any) => String(x?.checklist_no || '').trim())
      .filter((x: string) => !!x);
    if (nos.length === 0) {
      this.changeControlByChecklist = {};
      return;
    }
    this.service
      .get(
        'master/fg_sampling_checklist.php?type=getChangeControlStatusForChecklists&checklist_nos=' +
          encodeURIComponent(nos.join(','))
      )
      .subscribe((response: any) => {
        this.changeControlByChecklist = response && typeof response === 'object' ? response : {};
      }, () => {
        this.changeControlByChecklist = {};
      });
  }
}
