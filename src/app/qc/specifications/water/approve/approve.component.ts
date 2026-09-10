import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
})
export class ApproveComponent implements OnInit {
  specifications: any[] = [];
  selectedSpec: any = null;
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.service.getJsonArray('qc/specification/water.php?type=getSpecificationsLog&water_type=&status=').subscribe({
      next: (rows) => {
        const all = Array.isArray(rows) ? rows : [];
        this.specifications = all.filter((row: any) => this.isPendingApprovalStatus(row?.status));
        this.loading = false;
      },
      error: () => {
        this.specifications = [];
        this.loading = false;
      },
    });
  }

  private isPendingApprovalStatus(status: any): boolean {
    const key = String(status || '').trim().toLowerCase();
    return key === 'pending_approval' || key === 'pending approval' || key === 'checked';
  }

  view(spec: any): void {
    this.selectedSpec = spec;
  }

  back(): void {
    this.selectedSpec = null;
  }

  approve(): void {
    if (!this.selectedSpec?.id) { return; }
    this.service
      .get(
        'qc/specification/water.php?type=approveSpecification&id=' +
          this.selectedSpec.id +
          '&status=approved'
      )
      .subscribe({
        next: (r: any) => {
          if (r?.status === 'success') {
            alertify.success('Specification approved successfully');
            this.selectedSpec = null;
            this.load();
          } else {
            alertify.error('Approval failed, please try again');
          }
        },
        error: () => alertify.error('Approval failed, please try again'),
      });
  }

  reject(): void {
    if (!this.selectedSpec?.id) { return; }
    this.service
      .get(
        'qc/specification/water.php?type=approveSpecification&id=' +
          this.selectedSpec.id +
          '&status=rejected'
      )
      .subscribe({
        next: (r: any) => {
          if (r?.status === 'success') {
            alertify.success('Specification rejected');
            this.selectedSpec = null;
            this.load();
          } else {
            alertify.error('Reject failed, please try again');
          }
        },
        error: () => alertify.error('Reject failed, please try again'),
      });
  }
}
