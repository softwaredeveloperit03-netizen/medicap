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

  isNew = false;
  isPhoto=false;

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
  item=[];
  security_person='';
 department='';
 
  constructor(private service: DataAccessService,private datepipe:DatePipe) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getSecurityDetails();
    this.getDepartments();
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


  getSecurityDetails() {
    this.service.get('security/round.php?type=getSecurityDetails&department_name=' + this.department).subscribe(response => {
      this.results = response;
      this. filterItem();
    });
  }
  exitvisitor(id) {
    this.service.get('security/entry.php?type=exitVechile&id=' + id).subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Visitor Exited Successfully');
       this.getSecurityDetails();
      } else {
       alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  download() {
    this.service.open('security/round.php?type=downloadRound&department_name=' + this.department);
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['department'].toUpperCase().includes(this.department.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  AllRecord(){
    this.item =this.results;
    // this.security_person='';
    this.department='';
    
}
}
