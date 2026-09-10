import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  loading;
  // results=[];
  // results1=[];
  results;
  departments;
  water_type='';
  point_name='';
  department='';
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit() {
    this.getPointsLog();
   // this.getDepartments();
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

  // getDepartments(){
  //   this.service.get('common.php?type=getDepartments').subscribe(response => {
  //     this.departments = response; 
  //   });
  // }

  getPointsLog(){
    this.service.get('qc/water.php?type=getPointsLog').subscribe(response => {
      this.results=response;
      //this.results1=response;
    })
  }
  download(){
    this.service.open('qc/water.php?type=downloadPointsLog')
  }

  // filterStock(){
  //   this.results1 = [];
  //   for(let i=0; i<this.results1.length; i++){
  //     let data = this.results1[i];
  //     if(data.water_type.toUpperCase().includes(this.water_type.toUpperCase()) && data.point_name.toUpperCase().includes(this.point_name.toUpperCase()) && data.department.toUpperCase().includes(this.department.toUpperCase())){
  //       this.results1.push(data);
  //     }
  //   }
  // }

  // clear(){
  //   this.water_type='';
  //   this.point_name='';
  //   this.department='';
  //   //this.results = this.results1;
  // }

}
