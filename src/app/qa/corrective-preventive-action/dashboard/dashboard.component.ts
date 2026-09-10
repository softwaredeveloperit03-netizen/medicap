import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { FORM_A_NO, FORM_B_NO, SOP_REF } from '../capa070.utils';

@Component({
  selector: 'app-capa070-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  dept_head = 'No';
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  sopRef = SOP_REF;
  formA = FORM_A_NO;
  formB = FORM_B_NO;

  initiationCards = [
    { label: 'New CAPA Report', route: 'report/new', formNo: FORM_B_NO, icon: 'fa-plus-circle', show: 'user' },
    { label: 'CAPA Report Log', route: 'report/log', formNo: FORM_B_NO, icon: 'fa-list', show: 'all' },
  ];

  actionCards = [
    { label: 'Dept Approval for Execution', route: 'dept-approval', formNo: FORM_B_NO, icon: 'fa-user-check', show: 'dept_head' },
    { label: 'QA Approval & Number Assignment', route: 'qa-approval', formNo: FORM_B_NO, icon: 'fa-stamp', show: 'qa' },
    { label: 'CAPA Closing (Owner)', route: 'closing', formNo: FORM_B_NO, icon: 'fa-check-double', show: 'user' },
    { label: 'Closing Verification by QA', route: 'closing?mode=qa', formNo: FORM_B_NO, icon: 'fa-clipboard-check', show: 'qa' },
    { label: 'Effectiveness Check', route: 'effectiveness', formNo: FORM_B_NO, icon: 'fa-chart-line', show: 'all' },
  ];

  logCards = [
    { label: 'CAPA Tracking Log', route: 'log', formNo: FORM_A_NO, icon: 'fa-book', show: 'all' },
  ];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    const empId = localStorage.getItem('emp_id');
    const dept = localStorage.getItem('department') || '';
    if (empId) {
      this.service
        .get(
          'hr/employee.php?type=getrights&emp_id=' +
            encodeURIComponent(empId) +
            '&dep_name=' +
            encodeURIComponent(dept)
        )
        .subscribe((response: any) => {
          const r = Array.isArray(response) && response[0] ? response[0] : {};
          this.dept_head = r.dept_head || 'No';
          this.isuser = r.isuser || 'No';
          this.ischecker = r.ischecker || 'No';
          this.isapprover = r.isapprover || 'No';
          this.qms_approver = r.qms_approver || 'No';
        });
    }
  }

  canShow(show: string): boolean {
    if (show === 'all') {
      return true;
    }
    if (show === 'user') {
      return this.isuser === 'Yes' || this.dept_head === 'Yes' || this.isapprover === 'Yes';
    }
    if (show === 'dept_head') {
      return this.dept_head === 'Yes' || this.isapprover === 'Yes';
    }
    if (show === 'qa') {
      return (
        this.qms_approver === 'Yes' ||
        this.isapprover === 'Yes' ||
        this.dept_head === 'Yes' ||
        String(localStorage.getItem('department') || '')
          .toLowerCase()
          .includes('quality')
      );
    }
    return true;
  }
}
