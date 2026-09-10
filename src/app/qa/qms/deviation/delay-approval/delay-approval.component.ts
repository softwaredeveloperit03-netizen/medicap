import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-delay-approval',
  templateUrl: './delay-approval.component.html',
  styleUrls: ['./delay-approval.component.css'],
})
export class DelayApprovalComponent implements OnInit {
  isView = false;
  loading = false;
  saving = false;

  results: any[] = [];
  selected: any = null;
  approvalComment = '';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getPending();
  }

  getPending(): void {
    this.loading = true;
    this.service
      .get('deviation.php?type=getDelayJustificationsPendingApproval')
      .subscribe(
        (res: any) => {
          this.loading = false;
          this.results = Array.isArray(res) ? res : [];
        },
        () => {
          this.loading = false;
          this.results = [];
        }
      );
  }

  view(index: number): void {
    const row = this.results?.[index];
    if (!row) return;
    this.isView = true;
    this.approvalComment = '';
    this.loadDetail(row.id);
  }

  loadDetail(id: number): void {
    this.loading = true;
    this.service
      .get('deviation.php?type=getDelayJustification&id=' + id)
      .subscribe(
        (res: any) => {
          this.loading = false;
          this.selected = res && typeof res === 'object' ? res : null;
        },
        () => {
          this.loading = false;
          this.selected = null;
          alertify.error('Failed to load justification details.');
        }
      );
  }

  closeView(): void {
    this.isView = false;
    this.selected = null;
    this.approvalComment = '';
  }

  back(): void {
    if (this.isView) {
      this.closeView();
    } else {
      this.router.navigate(['/qa/qms/deviation']);
    }
  }

  print(): void {
    const el = document.getElementById('delayApprovalPrintArea');
    if (!el) {
      alertify.error('Print area not found.');
      return;
    }
    const devNo = String(this.selected?.deviation_no || this.selected?.deviationNo || 'DelayJustification')
      .replace(/[^a-zA-Z0-9_-]/g, '_');
    this.service.convertToPDF(el, `Delay_Justification_${devNo}`);
  }

  approve(): void {
    this.takeAction('approve');
  }

  reject(): void {
    this.takeAction('reject');
  }

  private takeAction(action: 'approve' | 'reject'): void {
    if (!this.selected?.id) {
      alertify.error('No justification selected.');
      return;
    }
    this.saving = true;
    const payload = {
      comment: this.approvalComment || '',
      action_by: localStorage.getItem('username') || localStorage.getItem('emp_id') || '',
      department: localStorage.getItem('department') || '',
    };
    this.service
      .post(
        'deviation.php?type=actionDelayJustification&id=' +
          this.selected.id +
          '&action=' +
          action,
        JSON.stringify(payload)
      )
      .subscribe(
        (res: any) => {
          this.saving = false;
          if (res?.status === 'success') {
            alertify.success(res?.message || 'Updated successfully.');
            this.closeView();
            this.getPending();
          } else {
            alertify.error(res?.message || 'Action failed.');
          }
        },
        () => {
          this.saving = false;
          alertify.error('Error processing request. Please try again.');
        }
      );
  }
}

