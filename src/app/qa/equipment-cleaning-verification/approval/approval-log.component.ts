import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { ecvHubPath } from '../ecv.utils';

declare let alertify: any;

@Component({
  selector: 'app-ecv-approval-log',
  templateUrl: './approval-log.component.html',
  styleUrls: ['../log/log.component.css', '../new/new.component.css'],
  providers: [DatePipe],
})
export class ApprovalLogComponent implements OnInit {
  results: any[] = [];
  selectedRecord: any = null;
  isView = false;
  fromDate = '';
  toDate = '';
  maxDate = '';
  hubPath = ecvHubPath();
  approvingId: number | null = null;
  qaDecision = 'Yes';
  qaComments = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    const today = new Date();
    this.fromDate = this.datePipe.transform(today, 'yyyy-MM-01') || '';
    this.toDate = this.datePipe.transform(today, 'yyyy-MM-dd') || '';
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    this.getLog();
  }

  getLog(): void {
    this.service
      .get(
        'qa/equipmentCleaningVerification.php?type=getEquipmentCleaningApprovalLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.qaDecision = record?.approved_for_production === 'No' ? 'No' : 'Yes';
    this.qaComments = record?.comments || '';
    this.isView = true;
  }

  statusLabel(status: string): string {
    if (status === 'approved') {
      return 'Approved';
    }
    if (status === 'not_approved') {
      return 'Not Approved';
    }
    if (status === 'pending_qa') {
      return 'Pending QA';
    }
    return 'Pending QC';
  }

  canQaApprove(record: any): boolean {
    return !!(record && !String(record.qa_by_date || '').trim());
  }

  approveQa(record: any): void {
    if (!record?.id || !this.canQaApprove(record) || this.approvingId === record.id) {
      return;
    }
    this.approvingId = record.id;
    this.service
      .post(
        'qa/equipmentCleaningVerification.php?type=updateEquipmentCleaningApprovalQa',
        JSON.stringify({
          id: record.id,
          approved_for_production: this.qaDecision,
          qa_meets_criteria: this.qaDecision,
          comments: this.qaComments,
        })
      )
      .subscribe(
        (response: any) => {
          this.approvingId = null;
          if (response?.status === 'success') {
            record.qa_by_date = response.qa_by_date;
            record.approved_for_production = this.qaDecision;
            record.qa_meets_criteria = this.qaDecision;
            record.status = response.approval_status;
            record.comments = this.qaComments;
            alertify.success('QA approval saved');
          } else {
            alertify.error(response?.status || 'Failed to save QA approval');
          }
        },
        () => {
          this.approvingId = null;
          alertify.error('Failed to save QA approval');
        }
      );
  }

  downloadLog(): void {
    this.service.open(
      'qa/equipmentCleaningVerification.php?type=downloadEquipmentCleaningApprovalLog&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadForm(id: number): void {
    this.service.open(
      'qa/equipmentCleaningVerification.php?type=downloadEquipmentCleaningApprovalForm&id=' + id
    );
  }
}
