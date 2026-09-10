import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'allocation', title: 'Sampling Allocation', route: 'allocation', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'new', title: 'New Sampling', route: 'new', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'inprocess', title: 'Samplings for Check', route: 'inprocess', icon: 'fa-search', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Samplings for Aprvl', route: 'checking', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'log', title: 'Samplings Report', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'inprocess', title: 'Samplings for Checking', route: 'inprocess', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Samplings for Approval', route: 'checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];

  isUser = false;
  isChecker = false;
  isApprover = false;
  
  // constructor() {
  //   this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
  //   this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
  //   this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  // }
  constructor(public service: DataAccessService) {
  }
    



  ngOnInit() {
    this.get_rights();

  }
  rights;
  righ;
  ischecker
isapprover
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Microbiology&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      console.log(this.rights)
      console.log(this.righ)
    });
  }


}
