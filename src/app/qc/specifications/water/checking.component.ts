import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-water-checking',
  templateUrl: './checking.component.html',
})
export class CheckingComponent implements OnInit {
  specifications: any[] = [];
  selectedSpec: any = null;
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    // Use same source as Water dashboard/log so queue stays in sync.
    this.service.getJsonArray('qc/specification/water.php?type=getSpecificationsLog&water_type=&status=').subscribe({
      next: (rows) => {
        const all = Array.isArray(rows) ? rows : [];
        this.specifications = all.filter((row: any) => this.isCheckingStatus(row?.status));
        this.loading = false;
      },
      error: () => {
        this.specifications = [];
        this.loading = false;
      },
    });
  }

  view(spec: any): void {
    this.selectedSpec = spec;
  }

  back(): void {
    this.selectedSpec = null;
  }

  approve(): void {
    this.updateStatus('pending_approval', 'Specification sent to approval');
  }

  reject(): void {
    this.updateStatus('rejected', 'Specification rejected');
  }

  private updateStatus(status: string, successMessage: string): void {
    if (!this.selectedSpec?.id) {
      return;
    }
    this.service
      .get(
        'qc/specification/water.php?type=checkSpecification&id=' +
          this.selectedSpec.id +
          '&status=' +
          encodeURIComponent(status)
      )
      .subscribe({
        next: (response: any) => {
          if (response?.status === 'success') {
            alertify.success(successMessage);
            this.selectedSpec = null;
            this.load();
          } else {
            alertify.error('Failed: An error occured, please try again!');
          }
        },
        error: () => alertify.error('Failed: An error occured, please try again!'),
      });
  }

  private isCheckingStatus(status: any): boolean {
    const key = String(status || '').trim().toLowerCase();
    return key === '' || key === 'checking' || key === 'pending';
  }
}
