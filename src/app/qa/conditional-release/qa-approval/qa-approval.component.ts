import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { statusClass } from '../cr.utils';

declare let alertify: any;

@Component({
  selector: 'app-conditional-release-approval',
  templateUrl: './qa-approval.component.html',
  styleUrls: ['../cr.shared.css'],
})
export class QaApprovalComponent implements OnInit {
  activeTab: 'pending' | 'final' = 'pending';
  pendingList: any[] = [];
  finalList: any[] = [];
  selectedEntry: any = null;
  isView = false;
  proceedProcessing = 'Yes';
  recordFinalRelease = false;
  rejectReason = '';
  declineReason = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadPending();
    this.loadFinalPending();
  }

  setTab(tab: 'pending' | 'final'): void {
    this.activeTab = tab;
  }

  loadPending(): void {
    this.service.get('qa/conditionalRelease.php?type=getPendingConditionalReleaseApprovals').subscribe((response: any) => {
      this.pendingList = Array.isArray(response) ? response : [];
    });
  }

  loadFinalPending(): void {
    this.service.get('qa/conditionalRelease.php?type=getApprovedConditionalReleaseForFinal').subscribe((response: any) => {
      this.finalList = Array.isArray(response) ? response : [];
    });
  }

  viewEntry(entry: any): void {
    this.service
      .get('qa/conditionalRelease.php?type=getConditionalReleaseById&id=' + entry.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedEntry = response;
          this.proceedProcessing = 'Yes';
          this.recordFinalRelease = false;
          this.rejectReason = '';
          this.declineReason = '';
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedEntry = null;
  }

  approve(): void {
    if (!this.selectedEntry?.id) return;
    const payload = {
      id: this.selectedEntry.id,
      proceed_processing: 'Yes',
      record_final_release: this.recordFinalRelease ? 'Yes' : 'No',
    };
    this.service
      .post('qa/conditionalRelease.php?type=approveConditionalReleaseRequest', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          const msg =
            response.final_release_recorded === 'Yes'
              ? 'Approved with CR# ' + (response.cr_no || '') + ' and Final Release recorded.'
              : 'Conditionally approved. CR# assigned: ' + (response.cr_no || '') + '. Record Final Release when documentation/testing is complete.';
          alertify.success(msg);
          this.closeView();
          this.loadPending();
          this.loadFinalPending();
        } else {
          alertify.error(response?.status || 'Approval failed');
        }
      });
  }

  decline(): void {
    if (!this.selectedEntry?.id) return;
    const reason = (this.declineReason || '').trim();
    if (!reason) {
      alertify.error('Please enter reason for declining (Proceed with Processing = No)');
      return;
    }
    const payload = {
      id: this.selectedEntry.id,
      proceed_processing: 'No',
      reject_reason: reason,
    };
    this.service
      .post('qa/conditionalRelease.php?type=approveConditionalReleaseRequest', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Request declined and logged with CR# N/A');
          this.closeView();
          this.loadPending();
        } else {
          alertify.error(response?.status || 'Decline failed');
        }
      });
  }

  reject(): void {
    if (!this.selectedEntry?.id) return;
    const reason = (this.rejectReason || '').trim();
    if (!reason) {
      alertify.error('Rejection reason is required');
      return;
    }
    const payload = {
      id: this.selectedEntry.id,
      reject_reason: reason,
    };
    this.service
      .post('qa/conditionalRelease.php?type=rejectConditionalReleaseRequest', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Request rejected. Requester can edit and resubmit.');
          this.closeView();
          this.loadPending();
        } else {
          alertify.error(response?.status || 'Rejection failed');
        }
      });
  }

  finalRelease(entry: any): void {
    if (!entry?.id) return;
    if (!confirm('Record QA Final Approval for Full Release for CR# ' + (entry.cr_no || entry.id) + '?')) {
      return;
    }
    this.service
      .post('qa/conditionalRelease.php?type=finalReleaseConditionalRelease', JSON.stringify({ id: entry.id }))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Final full release recorded for CR# ' + (entry.cr_no || entry.id));
          this.loadFinalPending();
        } else {
          alertify.error(response?.status || 'Failed to record final release');
        }
      });
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }
}
