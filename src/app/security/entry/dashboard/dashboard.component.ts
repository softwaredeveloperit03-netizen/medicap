import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
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
  item =[];
  filterargs;
  employeeList: Array<string>;
  materials = [];
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
  department_name = '';
  visitor_name='';
  vechile_type='';
  type;
  constructor(private service: DataAccessService,private datepipe:DatePipe) {
    this.isMobile = this.service.isMobile;
    this.from_date = this.datepipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datepipe.transform(Date.now(), 'yyyy-MM-dd');
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getVechileDetails();
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

  clear(){
    this.from_date='';
    this.to_date='';
    this.department_name='';
  }
  getVechileDetails() {
    this.service.get('security/entry.php?type=getVechileDetails' ).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.filterItem();
    });
  }

  canExit(tool: any): boolean {
    if (!tool) {
      return false;
    }
    const status = (tool.status || '').toLowerCase();
    if (status === 'exit' || status === 'exited') {
      return false;
    }
    return !this.hasExited(tool);
  }

  hasExited(tool: any): boolean {
    const outTime = tool?.out_time;
    return !!(outTime && outTime !== '0000-00-00 00:00:00' && outTime !== '0000-00-00');
  }

  exitVechile(id) {
    this.service.get('security/entry.php?type=exitVechile&id=' + id).subscribe((response: any) => {
      if (response['status'] === 'success') {
       alertify.success('Vehicle exited successfully');
        this.getVechileDetails();
      } else if (response['status'] === 'filled') {
       alertify.error('Vehicle already exited');
      } else {
       alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  // filterMaterial() {
  //   this.materials = [];
  //   for (let i = 0; i < this.results.length; i++) {
  //     let material = this.results[i];
  //     if (material['vechile_type'].toUpperCase().includes(this.vechile_type.toUpperCase())&&material['visitor_name'].toUpperCase().includes(this.visitor_name.toUpperCase())) {
  //       this.materials[this.materials.length] = material;
  //     }
  //   }
  // }

  download() {
    this.service.open('security/entry.php?type=downloadLog&from_date=' + this.from_date + '&to_date=' + this.to_date + '&department_name=' + this.department_name);
  }
  
filterItem() {
  this.item = [];
  for (let i = 0; i < this.results.length; i++) {
    let material = this.results[i];
    if (material['vechile_type'].toUpperCase().includes(this.vechile_type.toUpperCase())&&material['visitor_name'].toUpperCase().includes(this.visitor_name.toUpperCase())) {
      this.item[this.item.length] = material;
    }
  }
}
AllRecord(){
  this.item =this.results;
  this.vechile_type='';
  this.visitor_name='';
}
}