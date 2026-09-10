import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Params, } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe] 
})

export class DashboardComponent implements OnInit {
    selectedResult=[];
     equipment_id: string;
    isView=false;
constructor(private service:DataAccessService, private router: Router) { 
  this.loggedInDept = localStorage.getItem('department');

}

  ngOnInit(): void {
    this.monthly_data();
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

  result;;
  monthly_data() {
    this.service.get('qc/calibration/bulkdensity.php?type=get_monthly_calibration&department1=Store').subscribe(response => {
      this.result = response;
    });
  }
   
downloadReport2(){
  console.log(this.selectedResult['id']);
  this.service.open('qc/raw.php?type=downloadmonthly&department1=Store&id='+ this.selectedResult['id']);
}
 
view(index) {
   this.isView = true;
   this.selectedResult = this.result[index];
 }
 getDailyEquipments()
 {
  console.log('Search button clicked. Perform search operation.');
 }
}
