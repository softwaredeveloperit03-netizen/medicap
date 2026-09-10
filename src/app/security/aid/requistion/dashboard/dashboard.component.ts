
import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  item;
  item_name='';
  constructor(private service: DataAccessService,private datepipe:DatePipe) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getReuisition();
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
 getReuisition(){
   this.service.get('security/firstaid.php?type=getRequisition').subscribe(response => {
     this.results = response;
     this.filterItem();
   })
 }
 
 download(){
   this.service.open('security/firstaid.php?type=downloadRequisition&item_name=' + this.item_name)
 }

 filterItem() {
  this.item = [];
  for (let i = 0; i < this.results.length; i++) {
    let material = this.results[i];
    if (material['item_name'].toUpperCase().includes(this.item_name.toUpperCase())) {
      this.item[this.item.length] = material;
    }
  }
}
AllRecord(){
  this.item = this.results;
  this.item_name='';
}
  
}
