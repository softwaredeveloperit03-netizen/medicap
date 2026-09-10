import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-gmp-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master-checklist', title: 'GMP Mon Master Chl', route: 'master-checklist', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'revision', title: 'GMP Mon Revision', route: 'revision', icon: 'fa-sync-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'monitoring', title: 'GMP Monitoring', route: 'monitoring', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'memo', title: 'GMP Memo', route: 'memo', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'report', title: 'GMP Mon Reports', route: 'report', icon: 'fa-file-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'compliance', title: 'GMP Mon Compliance', route: 'compliance', icon: 'fa-file-signature', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'master-checklist', title: 'GMP Monitoring Master Checklist', route: 'master-checklist', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'revision', title: 'GMP Monitoring Revision', route: 'revision', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'report', title: 'GMP Monitoring Reports', route: 'report', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'compliance', title: 'GMP Monitoring Compliance', route: 'compliance', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
  ];


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.get_rights();

  }
  rights;
  righ;
  ischecker;
isapprover;
qms_approver;
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Quality Assurance&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      console.log(this.rights)
      console.log(this.righ)
      console.log(this.qms_approver)
    });
  }

}
