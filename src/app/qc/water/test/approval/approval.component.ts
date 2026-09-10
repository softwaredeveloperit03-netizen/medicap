import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView = false;
  results: any[] = [];
  selectedPlan: any = {};
  spec_tests: any[] = [];
  approval_remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingApprovals();
  }

  getPendingApprovals() {
    this.service.get('qc/water.php?type=getTestingApproval').subscribe((response: any) => {
      this.results = response || [];
    });
  }

  view(index: number) {
    this.selectedPlan = this.results[index] || {};
    this.spec_tests = Array.isArray(this.selectedPlan['tests']) ? this.selectedPlan['tests'] : [];
    this.approval_remark = this.selectedPlan['approval_remark'] || '';
    this.isView = true;
  }

  updateApproval(status: 'approve' | 'reject') {
    const remark = encodeURIComponent(this.approval_remark || '');
    const id = this.selectedPlan['id'];
    this.service.get('qc/water.php?type=approveTesting&id=' + id + '&status=' + status + '&remark=' + remark).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Updated Successfully');
        this.isView = false;
        this.getPendingApprovals();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
