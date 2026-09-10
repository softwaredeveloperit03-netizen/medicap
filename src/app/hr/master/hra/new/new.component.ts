import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MasterHubReturnService } from 'src/app/master/master-hub-return.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  selectedResult = [];
  leave_type;
  isLeavetype = false;
  leave: Object;
  salary_types;
  leaveList = [];
  designations;
  leave_deduct='';
  leavetypes;
  forward;
  constructor(
    private service: DataAccessService,
    private router: Router,
    private masterHubReturn: MasterHubReturnService
  ) { }

  cancelFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master/hra');
  }

  ngOnInit(): void {
    this.getDesignations();
    this.getSalaryTypes();
    this.getLeaveTypes();
  }
  getSalaryTypes() {
    this.service.get('hr/employee.php?type=getSalaryTypes').subscribe(response => {
      this.salary_types = response;
    });
    
  }
  getLeaveTypes() {
    this.leavetypes =[];
    this.service.get('hr/leavepolicy.php?type=getLeaveTypes').subscribe(response => {
      this.leavetypes = response;
    });
  }
  getLeavetype() {
    this.service.get('master/leavetype.php?type=getLeavetype').subscribe(response => {
      this.leave = response;
    })
  }

  getDesignations() {
    this.service.get('common.php?type=getDesignationHeading').subscribe(response => {
      this.designations = response;
    })
  }

  addLeavetype() {
    if (this.leave_type == 'ADD NEW') {
      this.isLeavetype = true;
    } else {
      this.isLeavetype = false;
    }
  }
  addData(data) {

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.leaveList[this.leaveList.length] = temp;
    data.resetForm();
  }
  delData(index) {
    this.leaveList.splice(index, 1);
  }

  saveLeaveType(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value; 
    var obj = {
      "leave_type" : temp['leave_type']
    }
    this.service.post('hr/leavepolicy.php?type=saveLeaveType', JSON.stringify(obj) ).subscribe(response => {
      if (response['status'] == 'success') {
       this.isLeavetype = false;
       this.getLeaveTypes();
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  save(data) {
    if (!data.valid) {
       alertify.error('All fields are required');
      return;
    }
    // if(this.leaveList.length==0){
    //   alertify.error('Please enter leave types');
    //   return;
    // }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['leaveList'] = this.leaveList;
    this.service.post('hr/leavepolicy.php?type=saveLeavePolicy', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/master/hra']);
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }
}
