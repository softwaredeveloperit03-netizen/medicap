import { ChangeDetectionStrategy, ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ApprovalComponent implements OnInit {
  results: any[] = [];

  constructor(
    private service: DataAccessService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.getRequests();
  }

  getRequests(): void {
    this.service.get('hr/password.php?type=getRequests').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.cdr.markForCheck();
    });
  }

  updatePassword(status: string, id: string | number): void {
    const encStatus = encodeURIComponent(String(status));
    const encId = encodeURIComponent(String(id));
    this.service
      .get('hr/password.php?type=updateRequest&status=' + encStatus + '&id=' + encId)
      .subscribe((response: any) => {
        if (response && response['status'] === 'success') {
          if (typeof alertify !== 'undefined') {
            alertify.success('Data Updated Successfully!');
          }
          this.getRequests();
        } else {
          if (typeof alertify !== 'undefined') {
            alertify.error('Failed, an error occurred. Please try again!');
          }
          this.cdr.markForCheck();
        }
      });
  }

  trackByIndex(_index: number): number {
    return _index;
  }

  trackById(_index: number, item: any): string | number {
    return item != null && item.id != null ? item.id : _index;
  }
}
