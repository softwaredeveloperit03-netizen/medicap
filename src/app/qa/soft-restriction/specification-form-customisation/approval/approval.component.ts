import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { SpecificationCustomisationLogMeta, SpecificationScope } from '../specification-form-customisation.constants';
import { SpecificationFormCustomisationService } from '../specification-form-customisation.service';
declare let alertify: any;

@Component({
  selector: 'app-specification-form-customisation-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
})
export class ApprovalComponent implements OnInit {
  scope: SpecificationScope = 'Raw Material';
  pending: SpecificationCustomisationLogMeta[] = [];
  loading = false;
  readonly scopes: SpecificationScope[] = ['Raw Material', 'Packing Material', 'Finish Product'];

  constructor(private service: SpecificationFormCustomisationService, private router: Router) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.service.getPendingApprovals(this.scope).subscribe({
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

  setScope(scope: SpecificationScope): void {
    this.scope = scope;
    this.load();
  }

  approve(row: SpecificationCustomisationLogMeta): void {
    if (!row?.id) return;
    const approvalBy = localStorage.getItem('username') || 'Unknown';
    this.service.approve(this.scope, Number(row.id), approvalBy).subscribe((res: any) => {
      if (res?.status === 'success') {
        alertify.success(res?.message || 'Approved');
        this.load();
      } else {
        alertify.error(res?.message || 'Approval failed');
      }
    }, () => alertify.error('Approval failed'));
  }

  reject(row: SpecificationCustomisationLogMeta): void {
    if (!row?.id) return;
    this.service.reject(this.scope, Number(row.id)).subscribe((res: any) => {
      if (res?.status === 'success') {
        alertify.success(res?.message || 'Rejected');
        this.load();
      } else {
        alertify.error(res?.message || 'Reject failed');
      }
    }, () => alertify.error('Reject failed'));
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/specification-form-customisation']);
  }
}
