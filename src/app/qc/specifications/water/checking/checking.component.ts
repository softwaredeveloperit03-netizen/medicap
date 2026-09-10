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
    this.service.getJsonArray('qc/specification/water.php?type=getPendingSpecifications').subscribe({
      next: (rows) => {
        this.specifications = Array.isArray(rows) ? rows : [];
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
}
