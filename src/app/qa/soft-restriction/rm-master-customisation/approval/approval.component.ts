import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { RmMasterCustomisationService } from '../rm-master-customisation.service';
import { RMCustomisationRecord } from '../rm-master-customisation.constants';
import { GmpMaterialFormCustomisationService, GmpFormLogMeta } from '../gmp-material-form-customisation.service';
declare let alertify: any;

@Component({
  selector: 'app-rm-master-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  pending: RMCustomisationRecord[] = [];
  gmpPending: GmpFormLogMeta[] = [];

  constructor(
    private service: RmMasterCustomisationService,
    private gmp: GmpMaterialFormCustomisationService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.service.getPendingApprovals().subscribe((res: any) => {
      this.pending = Array.isArray(res) ? res : [];
    });
    this.gmp.getPendingApprovals().subscribe((res: any) => {
      this.gmpPending = Array.isArray(res) ? res : [];
    });
  }

  approve(row: RMCustomisationRecord): void {
    if (!row.id) return;
    const approvalBy = localStorage.getItem('username') || 'Unknown';
    this.service.approve(row.id, approvalBy).subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success(res.message || 'Approved. Customisation is now implemented in QC module.');
        this.load();
      } else {
        alertify.error(res?.message || 'Approval failed.');
      }
    }, () => alertify.error('Request failed.'));
  }

  reject(row: RMCustomisationRecord): void {
    if (!row.id) return;
    this.service.reject(row.id).subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success(res.message || 'Rejected.');
        this.load();
      } else {
        alertify.error(res?.message || 'Reject failed.');
      }
    }, () => alertify.error('Request failed.'));
  }

  approveGmp(row: GmpFormLogMeta): void {
    if (!row.id) return;
    const approvalBy = localStorage.getItem('username') || 'Unknown';
    this.gmp.approve(row.id, approvalBy).subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success(res.message || 'Approved.');
        this.load();
      } else {
        alertify.error(res?.message || 'Approval failed.');
      }
    }, () => alertify.error('Request failed.'));
  }

  rejectGmp(row: GmpFormLogMeta): void {
    if (!row.id) return;
    this.gmp.reject(row.id).subscribe((res: any) => {
      if (res && res.status === 'success') {
        alertify.success(res.message || 'Rejected.');
        this.load();
      } else {
        alertify.error(res?.message || 'Reject failed.');
      }
    }, () => alertify.error('Request failed.'));
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/rm-master-customisation']);
  }
}
