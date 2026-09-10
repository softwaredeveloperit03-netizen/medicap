import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'under-test', title: 'Under Test', route: 'under-test', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qc', title: 'QC Approved', route: 'qc', icon: 'fa-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'qa', title: 'QA Released', route: 'qa', icon: 'fa-check-double', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];


  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.get_rights();

  }
  rights;
  righ;
  dept_head;
  isapprover;
 
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Store&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.dept_head=this.rights[0].dept_head
      this.isapprover=this.rights[0].isapprover
      console.log(this.rights)
      console.log(this.righ)
      console.log(this.dept_head)
      

    });
  } 

}
