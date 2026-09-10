import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sqr-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  dept_head = 'No';
  pendingStepwiseCount = 0;
  pendingFinalCount = 0;

  forms = [
    {
      label: 'Stepwise Batch Record Review & Release',
      route: 'stepwise/log',
      formNo: 'FQA-035-B',
      icon: 'fa-clipboard-check',
    },
    {
      label: 'Final Release Checklist — Finished Product',
      route: 'final/log',
      formNo: 'FQA-035-A',
      icon: 'fa-certificate',
    },
  ];

  approvalCard = {
    label: 'QA Head Approval',
    route: 'approval',
    formNo: 'SOP-QA-035',
    icon: 'fa-user-check',
  };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    const empId = localStorage.getItem('emp_id');
    if (empId) {
      this.service.get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(empId)).subscribe((response: any) => {
        const r = Array.isArray(response) && response[0] ? response[0] : {};
        this.dept_head = r.dept_head || 'No';
      });
    }
    this.service.get('qa/stepwiseQaRelease.php?type=getPendingStepwiseApprovals').subscribe((response: any) => {
      this.pendingStepwiseCount = Array.isArray(response) ? response.length : 0;
    });
    this.service.get('qa/stepwiseQaRelease.php?type=getPendingFinalReleaseApprovals').subscribe((response: any) => {
      this.pendingFinalCount = Array.isArray(response) ? response.length : 0;
    });
  }

  get pendingTotal(): number {
    return this.pendingStepwiseCount + this.pendingFinalCount;
  }
}
