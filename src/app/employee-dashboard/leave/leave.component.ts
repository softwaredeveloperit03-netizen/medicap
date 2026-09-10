import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-leave',
  templateUrl: './leave.component.html',
  styleUrls: ['./leave.component.css']
})
export class LeaveComponent implements OnInit {
  emp_id = '';
  departments;
  approver;
  approvers;
  department_name = '';
  charge_handover;
  employees;
  pendingList = [];
  selectedResult = [];
  handovers;
  persons;
  employees1;

  leave_from;
  leave_to;
  no_day0:number = 0;
  no_day:number = 0;

  
  constructor(private service: DataAccessService, private router: Router) { 
    this.emp_id = localStorage.getItem('loger_id');
    this.department_name = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.getDepartments();
    this.getLeaveTypes();
     
    // this.getDeptalternateperson();
    this.emp_id = localStorage.getItem('loger_id');
    this.department_name = localStorage.getItem('department');
    this.getDeptHandover();
    this.getDeptByManagers(this.department_name);
  }


  leavetypes;

  getLeaveTypes() {
    this.leavetypes =[];
    this.service.get('hr/leavepolicy.php?type=getLeaveTypesEmployee').subscribe(response => {
      this.leavetypes = response;
    });
  }


  calculateDays() {
    this.getweekoff(this.leave_from,this.leave_to);

    if (this.leave_from && this.leave_to) {
      const start = new Date(this.leave_from);
      const end = new Date(this.leave_to);
  
      let totalDays = 0;
  
      // for (let current = start; current <= end; current.setDate(current.getDate() + 1)) {
      //   if (current.getDay() !== 0) { // Check if it's not Sunday
      //     totalDays++;
      //   }
      // }
      // this.no_day0 = totalDays;
      const timeDifference = Math.abs(end.getTime() - start.getTime()) + (24 * 60 * 60 * 1000);
      this.no_day0 = Math.ceil(timeDifference / (1000 * 60 * 60 * 24));

  
      this.no_day = this.no_day0 - this.weekends;
    } else {
      this.no_day = null;
    }
  }


  weekofdata;
  weekends;
  getweekoff(start,end) {
    this.service.get('hr/leaveForm.php?type=getweekoff&start='+start +'&too='+end +'&EMP_ID='+this.emp_id).subscribe(response => {
      this.weekofdata = response;
      this.weekends=this.weekofdata[0]['total_count']
      console.log('this.weekends :>> ', this.weekends);
    })
  }
  


  getDepartmentsEmployee(value) {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
      this.employees1 = response;
    });
  }


  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    })
  }

  getDeptByManagers(value) {
    this.service.get('employee.php?type=getdeptHead&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
    this.service.get('employee.php?type=getDeptHandover&department_name=' + this.department_name).subscribe(response => {
      this.handovers = response;
    });
    // let temp = [];
    // temp['emp_id'] = this.charge_handover['emp_id'];
    this.service.get('employee.php?type=getDeptalternateperson&department_name=' + this.department_name +'&empid='+this.emp_id ).subscribe(response => {
      this.persons = response;
    });
  }


  empdate;
  bleave =false;
  bleave1;
  
  getDeptHandover() {

    this.bleave = true;
    this.service.get('employee.php?type=getDeptalternateperson&department_name=' + this.department_name +'&empid='+this.emp_id ).subscribe(response => {
      this.persons = response;
    });

    this.service.get('hr/leaveForm.php?type=getbalanceleave&empid1='+this.emp_id ).subscribe(response => {
      this.empdate = response;
      this.bleave1=this.empdate[0]['leave_balance']
    });


  }

  // getDeptalternateperson() {
  //   this.service.get('employee.php?type=getDeptalternateperson&department_name=' + this.department_name + '&emp_no=' + this.selectedResult["emp_no"]).subscribe(response => {
  //     this.persons = response;
  //   })
  // }


  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required')
    }
    let temp = data.value;
    this.pendingList[this.pendingList.length] = temp;
    data.resetForm();

  }

  delData(index) {

    this.pendingList.splice(index, 1);

  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['pendingList'] = this.pendingList;
    temp['emp_id'] = this.emp_id;
    temp['department_name'] = this.department_name;
    this.service.post('hr/leaveForm.php?type=saveLeaveForm', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/employee-dashboard/leave_status'])
                                      
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }
    
}
