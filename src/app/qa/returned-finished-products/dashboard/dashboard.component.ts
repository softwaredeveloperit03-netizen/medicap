import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rfp-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  dept_head = 'No';
  pendingQaCount = 0;

  forms = [
    {
      label: 'Return of Merchandise Report',
      route: 'report/log',
      formNo: 'FQA-023-A',
      icon: 'fa-file-alt',
    },
    {
      label: 'Return of Merchandise Log',
      route: 'log',
      formNo: 'FQA-023-B',
      icon: 'fa-clipboard-list',
    },
  ];

  approvalCard = {
    label: 'QA Head Review',
    route: 'approval',
    formNo: 'SOP-QA-023',
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
    this.service.get('qa/returnedFinishedProducts.php?type=getPendingReturnMerchandiseQa').subscribe((response: any) => {
      this.pendingQaCount = Array.isArray(response) ? response.length : 0;
    });
  }
}
