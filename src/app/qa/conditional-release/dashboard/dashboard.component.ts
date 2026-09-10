import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-cr-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  dept_head = 'No';

  forms = [
    {
      label: 'Conditional Release Request',
      route: 'request/log',
      formNo: 'FQA-020-A',
      icon: 'fa-file-medical',
    },
    {
      label: 'Conditional Release Tracking Log',
      route: 'tracking/log',
      formNo: 'FQA-020-B',
      icon: 'fa-clipboard-list',
    },
  ];

  approvalCard = {
    label: 'QA Head Approval',
    route: 'approval',
    formNo: 'SOP-QA-020',
    icon: 'fa-user-check',
  };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    const empId = localStorage.getItem('emp_id');
    if (empId) {
      this.service
        .get('hr/employee.php?type=getrights&emp_id=' + encodeURIComponent(empId))
        .subscribe((response: any) => {
          const r = Array.isArray(response) && response[0] ? response[0] : {};
          this.dept_head = r.dept_head || 'No';
        });
    }
  }
}
