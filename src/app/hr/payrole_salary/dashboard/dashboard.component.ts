import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'approval', title: 'Prepare Payroll', route: 'approval', icon: 'fa-calculator', category: 'Payroll', gradient: 'linear-gradient(135deg, #0ea5e9 0%, #1d4ed8 100%)' },
    { id: 'statement', title: 'Payroll Statement', route: 'statement', icon: 'fa-file-invoice-dollar', category: 'Payroll', gradient: 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)' },
    { id: 'analytics', title: 'Salary Analytics', route: 'analytics', icon: 'fa-chart-pie', category: 'Payroll', gradient: 'linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%)' },
    { id: 'register', title: 'Salary Register', route: 'register', icon: 'fa-book', category: 'Payroll', gradient: 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)' },
    { id: 'matrix', title: 'Approval Matrix', route: 'matrix', icon: 'fa-sitemap', category: 'Payroll', gradient: 'linear-gradient(135deg, #db2777 0%, #9d174d 100%)' },
    { id: 'payment', title: 'Accounts Payment', route: 'payment', icon: 'fa-university', category: 'Accounts', gradient: 'linear-gradient(135deg, #0f766e 0%, #115e59 100%)' },
    { id: 'annexure', title: 'Salary Annexure', route: '/hr/employees/annexure', icon: 'fa-file-alt', category: 'Master', gradient: 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)' },
    { id: 'tds', title: 'TDS Statement', route: '/account/TDSProejctionStatement', icon: 'fa-percentage', category: 'TDS', gradient: 'linear-gradient(135deg, #b45309 0%, #92400e 100%)' },
    { id: 'ostatement', title: 'Legacy Statement', route: 'ostatement', icon: 'fa-archive', category: 'Payroll', gradient: 'linear-gradient(135deg, #64748b 0%, #475569 100%)' },
  ];

  plant_id;
  loggedInDept;
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  constructor(private service: DataAccessService) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.get_rights();
  }

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        if (this.rights?.[0]) {
          this.isuser = this.rights[0].isuser;
          this.ischecker = this.rights[0].ischecker;
          this.isapprover = this.rights[0].isapprover;
          this.qms_approver = this.rights[0].qms_approver;
          this.dept_head = this.rights[0].dept_head;
          this.isauditor = this.rights[0].isauditor;
          this.plant_head = this.rights[0].plant_head;
          this.shift_allocator = this.rights[0].shift_allocator;
        }
      });
  }
}
