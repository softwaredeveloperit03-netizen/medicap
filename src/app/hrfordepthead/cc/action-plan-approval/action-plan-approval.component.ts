import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-action-plan-approval',
  templateUrl: './action-plan-approval.component.html',
  styleUrls: ['./action-plan-approval.component.css']
})
export class ACTIONPLANAPPROVALComponent implements OnInit {

  @Input() selectedResult: any = {};
  @Input() savedFormData: any = {};
  @Output() onClose = new EventEmitter<void>();
  @Output() onSave = new EventEmitter<any>();

  actionPlanApprovalStatus: string = '';
  actionPlanAssignedTo: string = '';
  targetCompletionDate: string = '';
  isView: boolean = false;
  results: any[] = [];
  currentSelectedResult: any = {};
  approvalStatusOptions = [
    { value: '', label: '-- Select --' },
    { value: 'Approved', label: 'Approved' },
    { value: 'Not Approved', label: 'Not Approved' }
  ];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    // Always show list view first, then user can click view to see form
    this.getQaApprovalRecords();
    
    // If selectedResult is passed from parent (from depreview), merge the data
    if (this.selectedResult && this.selectedResult['id']) {
      this.currentSelectedResult = { ...this.selectedResult };
      // Merge saved form data if available
      if (this.savedFormData && Object.keys(this.savedFormData).length > 0) {
        this.currentSelectedResult = { ...this.currentSelectedResult, ...this.savedFormData };
      }
      // After fetching records, check if this record exists and highlight it
      setTimeout(() => {
        const foundIndex = this.results.findIndex(r => r.id === this.selectedResult['id']);
        if (foundIndex !== -1) {
          // Record found in list, user can click view
          this.currentSelectedResult = this.results[foundIndex];
        }
      }, 500);
    }
  }

  getQaApprovalRecords() {
    const plantId = localStorage.getItem('plant_id') || '';
    this.service
      .get('changecontrol1.php?type=getCcQAByMeha&plant_id=' + encodeURIComponent(plantId))
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
        // If selectedResult was passed from parent, find and update it
        if (this.selectedResult && this.selectedResult['id']) {
          const foundRecord = this.results.find((r: any) => r.id === this.selectedResult['id']);
          if (foundRecord) {
            this.currentSelectedResult = { ...foundRecord, ...this.savedFormData };
          }
        }
      }, (error: any) => {
        console.error('Error fetching QA approval records:', error);
        this.results = [];
      });
  }

  view(index: number) {
    this.currentSelectedResult = this.results[index];
    this.isView = true;
    this.loadApprovalData();
  }

  loadApprovalData() {
    if (!this.currentSelectedResult) return;
    // APPROVAL_QA is read-only (filled in qaHeadReview tab)
    this.actionPlanApprovalStatus = this.currentSelectedResult['qa_head_remark'] || '';
    this.actionPlanAssignedTo = this.currentSelectedResult['action_plan_assigned_to'] || '';
    this.targetCompletionDate = this.currentSelectedResult['target_completion_date'] ? this.currentSelectedResult['target_completion_date'].toString().substring(0, 10) : '';
  }

  close() {
    if (this.isView) {
      this.isView = false;
      this.currentSelectedResult = {};
      this.actionPlanApprovalStatus = '';
      this.actionPlanAssignedTo = '';
      this.targetCompletionDate = '';
      this.getQaApprovalRecords();
    } else {
      // Close list view - if called from parent, emit event, otherwise just close
      if (this.selectedResult && this.selectedResult['id']) {
        this.onClose.emit();
      }
    }
  }

  saveApproval(form: any) {
    if (!this.actionPlanApprovalStatus || !this.actionPlanAssignedTo || !this.targetCompletionDate) {
      alertify.error('Please fill Approved/Not Approved, Action plan assigned to, and Target completion date');
      return;
    }

    const recordId = this.currentSelectedResult['id'] || this.selectedResult['id'];
    if (!recordId) {
      alertify.error('Record ID not found');
      return;
    }

    // Preserve APPROVAL_QA from record (filled in qaHeadReview tab) – read-only in this form
    const payload = {
      APPROVAL_QA: this.currentSelectedResult['APPROVAL_QA'] || '',
      action_plan_approval_status: this.actionPlanApprovalStatus,
      action_plan_assigned_to: this.actionPlanAssignedTo,
      target_completion_date: this.targetCompletionDate
    };

    this.service
      .post(
        'changecontrol1.php?type=saveActionPlanApproval&id=' + recordId,
        JSON.stringify(payload)
      )
      .subscribe((response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('Change Control Updated Successfully');
          this.onSave.emit(payload);
          this.isView = false;
          this.currentSelectedResult = {};
          this.actionPlanApprovalStatus = '';
          this.actionPlanAssignedTo = '';
          this.targetCompletionDate = '';
          this.getQaApprovalRecords();
          if (this.selectedResult && this.selectedResult['id']) {
            setTimeout(() => this.onClose.emit(), 1000);
          }
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      }, (error: any) => {
        alertify.error('Error updating change control');
      });
  }
}

