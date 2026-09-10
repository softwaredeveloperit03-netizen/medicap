import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'specification', title: 'Specifications', route: '/microbiology/water/specification', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'water-sampling-plan', title: 'Sampling & Testing Plan', route: '/microbiology/water/plan', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'points', title: 'Sampling Points', route: '/microbiology/water/points', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'plan', title: 'Sampling Plan', route: '/microbiology/water/plan', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'sampling-allocation', title: 'Plan Allocation', route: '/microbiology/water/sampling-allocation', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'sampling', title: 'Water Sampling', route: '/microbiology/water/sampling', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'testing', title: 'Testing', route: '/microbiology/water/testing', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'testing-checking', title: 'Testing Checking', route: '/microbiology/water/testing-checking', icon: 'fa-check-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'water', title: 'Microbiological Analysis', route: '/microbiology/bacteriological', icon: 'fa-microscope', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'report', title: 'AR Reports', route: '/microbiology/water/report', icon: 'fa-file-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'coa', title: 'COA Reports', route: '/microbiology/water/coa', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'trend', title: 'Trends', route: '/microbiology/water/trend', icon: 'fa-chart-line', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
  ];


  constructor(public service: DataAccessService) {}

  ngOnInit() {
    this.get_rights();

  }
  rights;
  righ;
  dept_head;
  isapprover;
 
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Microbiology&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
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
