import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ReceivingCustomisationLogMeta } from '../receiving-form-customisation.constants';
import { ReceivingFormCustomisationService } from '../receiving-form-customisation.service';
declare let alertify: any;

@Component({
  selector: 'app-receiving-form-customisation-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
})
export class ApprovalComponent implements OnInit {
  pending: ReceivingCustomisationLogMeta[] = [];
  loading = false;

  constructor(private service: ReceivingFormCustomisationService, private router: Router) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.service.getPendingApprovals().subscribe({
      next: (res: any) => {
        this.pending = Array.isArray(res) ? res : [];
        this.loading = false;
      },
      error: () => {
        this.pending = [];
        this.loading = false;
      },
    });
  }

  approve(row: ReceivingCustomisationLogMeta): void {
    if (!row?.id) return;
    const approvalBy = localStorage.getItem('username') || 'Unknown';
    this.service.approve(Number(row.id), approvalBy).subscribe((res: any) => {
      if (res?.status === 'success') {
        alertify.success(res?.message || 'Approved');
        this.load();
      } else {
        alertify.error(res?.message || 'Approval failed');
      }
    }, () => alertify.error('Approval failed'));
  }

  reject(row: ReceivingCustomisationLogMeta): void {
    if (!row?.id) return;
    this.service.reject(Number(row.id)).subscribe((res: any) => {
      if (res?.status === 'success') {
        alertify.success(res?.message || 'Rejected');
        this.load();
      } else {
        alertify.error(res?.message || 'Reject failed');
      }
    }, () => alertify.error('Reject failed'));
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/receiving-form-customisation']);
  }
}
