import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EFFECTIVE_DATE,
  FORM_B_NO,
  REVISION_NO,
  SOP_REF,
  canRecordFinalRelease,
  defaultFromDate,
  defaultToDate,
  displayApproverName,
  statusClass,
} from '../cr.utils';

declare let alertify: any;

@Component({
  selector: 'app-conditional-release-tracking-log',
  templateUrl: './tracking-log.component.html',
  styleUrls: ['../cr.shared.css'],
  providers: [DatePipe],
})
export class TrackingLogComponent implements OnInit {
  formNo = FORM_B_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;

  results: any[] = [];
  selectedRecord: any = null;
  isView = false;
  fromDate = '';
  toDate = '';
  maxDate = '';
  dept_head = 'No';
  stamping = false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    this.loadRights();
    this.getLog();
  }

  loadRights(): void {
    const empId = localStorage.getItem('emp_id');
    if (!empId) {
      return;
    }
    this.service
      .get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(empId))
      .subscribe((response: any) => {
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.dept_head = r.dept_head || 'No';
      });
  }

  getLog(): void {
    this.service
      .get(
        'qa/conditionalRelease.php?type=getConditionalReleaseTrackingLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.service
      .get('qa/conditionalRelease.php?type=getConditionalReleaseById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  approverName(record: any, field: 'requested' | 'qa' | 'final'): string {
    if (field === 'requested') {
      return record?.requested_by_display || displayApproverName(record?.requested_by);
    }
    if (field === 'qa') {
      return record?.qa_approval_by_display || displayApproverName(record?.qa_approval_by);
    }
    return record?.final_approval_by_display || displayApproverName(record?.final_approval_by);
  }

  approverDate(record: any, field: 'requested' | 'qa' | 'final'): string {
    if (field === 'requested') {
      return record?.requested_by_date || '';
    }
    if (field === 'qa') {
      return record?.qa_approval_date_display || record?.qa_approval_date || '';
    }
    return record?.final_approval_date_display || record?.final_approval_date || '';
  }

  canFinalRelease(record: any): boolean {
    return this.dept_head === 'Yes' && (record?.can_final_release || canRecordFinalRelease(record));
  }

  finalRelease(record: any): void {
    if (!this.canFinalRelease(record) || this.stamping) {
      return;
    }
    if (!confirm('Record QA Final Approval for Full Release for CR# ' + (record.cr_no || record.id) + '?')) {
      return;
    }
    this.stamping = true;
    this.service
      .post('qa/conditionalRelease.php?type=finalReleaseConditionalRelease', JSON.stringify({ id: record.id }))
      .subscribe(
        (response: any) => {
          this.stamping = false;
          if (response?.status === 'success') {
            alertify.success('Final full release recorded');
            this.getLog();
            if (this.selectedRecord?.id === record.id) {
              this.view(record);
            }
          } else {
            alertify.error(response?.status || 'Failed to record final release');
          }
        },
        () => {
          this.stamping = false;
          alertify.error('Failed to record final release');
        }
      );
  }

  downloadLog(): void {
    this.service.open(
      'qa/conditionalRelease.php?type=downloadConditionalReleaseTrackingLog&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadRequest(id: number): void {
    this.service.open('qa/conditionalRelease.php?type=downloadConditionalReleaseRequestForm&id=' + id);
  }
}
