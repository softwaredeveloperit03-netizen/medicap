import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { statusClass, statusLabel } from '../capa070.utils';

declare let alertify: any;

@Component({
  selector: 'app-capa070-qa-approval',
  templateUrl: './qa-approval.component.html',
  styleUrls: ['../capa070.shared.css'],
})
export class QaApprovalComponent implements OnInit {
  pendingList: any[] = [];
  selectedRecord: any = null;
  isView = false;
  rejectReason = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadPending();
  }

  loadPending(): void {
    this.service
      .get('qa/correctivePreventiveAction.php?type=getPendingCapa&stage=qa')
      .subscribe((response: any) => {
        this.pendingList = Array.isArray(response) ? response : [];
      });
  }

  viewRecord(record: any): void {
    this.service
      .get('qa/correctivePreventiveAction.php?type=getCapaById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.rejectReason = '';
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
    this.rejectReason = '';
  }

  approve(): void {
    if (!this.selectedRecord?.id) {
      return;
    }
    this.service
      .post(
        'qa/correctivePreventiveAction.php?type=approveCapaQa',
        JSON.stringify({ id: this.selectedRecord.id })
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('CAPA approved. Number assigned: ' + (response.capa_no || ''));
          this.closeView();
          this.loadPending();
        } else {
          alertify.error(response?.status || 'Approval failed');
        }
      });
  }

  reject(): void {
    if (!this.selectedRecord?.id) {
      return;
    }
    const reason = (this.rejectReason || '').trim();
    if (!reason) {
      alertify.error('Rejection reason is required');
      return;
    }
    this.service
      .post(
        'qa/correctivePreventiveAction.php?type=rejectCapaQa',
        JSON.stringify({ id: this.selectedRecord.id, reject_reason: reason })
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('CAPA rejected. Initiator can edit and resubmit.');
          this.closeView();
          this.loadPending();
        } else {
          alertify.error(response?.status || 'Rejection failed');
        }
      });
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  getStatusLabel(status: string): string {
    return statusLabel(status);
  }
}
