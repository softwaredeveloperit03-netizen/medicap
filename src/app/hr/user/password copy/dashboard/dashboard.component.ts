import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  departments;
  designations;
  constructor(private service:DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit() {
    this.getRequestsLog();
    this.getDepartments();
    // this.getDesignations();
    this.get_rights();
  }
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }

  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
  // departments;
  department;
  // designations;
  getDesignation(data) {
    let value = data.value;
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == value){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }

  getRequestsLog(){
    this.service.get('hr/password.php?type=getRequestsLog').subscribe(response => {
      this.results = response;
    });
  }
  // getDepartments() {
  //   this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
  //     this.departments = response;
  //   });
  // }

  // getDesignations() {
  //   this.service.get('hr/employee.php?type=getDesignations').subscribe(response => {
  //     this.designations = response;
  //   });
  // }
}
