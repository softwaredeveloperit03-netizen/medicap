import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  visitorName='';
  isMobile = false;
  isView = false;
  isNew = false;
  isPhoto=false;
  selectedResult = [];
  filterargs;
  employeeList: Array<string>;

  meeting: string;
  employeeMasterList;
  newEmployee: Array<any>;
  results;
  category;
  departments;
  employees;
  from_date = '';
  to_date = '';
  results1=[];
  department = '';
  designations;
  aids=[];
  constructor(private service: DataAccessService,private datepipe:DatePipe) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getAidDetails();
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


  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  getAidDetails() {
    this.service.get('security/firstaid.php?type=getFisrtAidDetails').subscribe((response:any) => {
      this.results = response;
      this.filterAids();

    });
  }

  

  download() {
    this.service.open('security/firstaid.php?type=downloadFirstAidLog&department_name=' + this.department);
  }

  filterAids(){
    this.aids = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['department'].toUpperCase().includes(this.department.toUpperCase())) {
        this.aids[this.aids.length] = material;
      }
    }
  }

  AllRecord(){
    this.aids= this.results;
    this.department='';
  }
}
