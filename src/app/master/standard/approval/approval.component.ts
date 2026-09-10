import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-standard-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  rows: any[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPending();
  }

  getPending() {
    this.service.get('qc/standard.php?type=getPendingStandards').subscribe((response: any) => {
      this.rows = Array.isArray(response) ? response : [];
    });
  }

  update(status: 'approve' | 'reject', id: any) {
    this.service.get('qc/standard.php?type=updateStandard&status=' + status + '&id=' + id).subscribe((response: any) => {
      if (response?.status === 'success') {
        alertify.success('Record updated successfully');
        this.getPending();
      } else {
        alertify.error('Failed to update record');
      }
    });
  }
}
