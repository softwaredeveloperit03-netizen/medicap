import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'res', title: 'Resignation', route: 'res', icon: 'fa-user-times', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'acceptance', title: 'Resign. Acceptance', route: 'acceptance', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'clearance', title: 'Employee Clearance', route: 'clearance', icon: 'fa-user-lock', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'interview', title: 'Exit Interview', route: 'interview', icon: 'fa-comments', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'letter', title: 'Experience Letter', route: 'letter', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'acceptance', title: 'Resignation Acceptance', route: 'acceptance', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.get_rights();

  }

  rights;
  righ;
  ischecker
isapprover
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Human Resource&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      console.log(this.rights)
      console.log(this.righ)
    });
  }


}
