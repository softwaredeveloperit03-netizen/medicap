import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'plan', title: 'Self Inspection Plan', route: 'plan', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'team', title: 'Self Inspection Team', route: 'team', icon: 'fa-users', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'annoucement', title: 'Self Inspection Announcement', route: 'annoucement', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'auditcheck', title: 'Checklist Master', route: 'Auditcheck', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'checklist', title: 'Department Checklists', route: 'checklist', icon: 'fa-th-list', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'report', title: 'Self Inspection Report', route: 'report', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'log', title: 'Inspection Log Book', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.get_rights();

  }
  rights;
  righ;
  ischecker
isapprover
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Quality Assurance&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      console.log(this.rights)
      console.log(this.righ)
    });
  }

}
