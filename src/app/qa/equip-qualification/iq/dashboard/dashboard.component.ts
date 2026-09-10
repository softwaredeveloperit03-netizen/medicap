import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'new', title: 'New Qualification', route: 'new', icon: 'fa-plus', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Checking Qualificaton', route: 'checking', icon: 'fa-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'approval', title: 'Inprocess Qualificaton', route: 'approval', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'log', title: 'Report', route: 'log', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  results;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getIQLog();
    this.get_rights();

  }

  getIQLog() {
    this.service.get('qa/qualification.php?type=getRequest').subscribe(response => {
      this.results = response;
    });
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
