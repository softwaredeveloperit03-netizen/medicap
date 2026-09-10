import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-coff',
  templateUrl: './coff.component.html',
  styleUrls: ['./coff.component.css']
})
export class CoffComponent implements OnInit {
  emp_id = '';
  departments;
  approver;
  leave_type='C OFF';
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
  no_day:number = 0;

  
  constructor(private service: DataAccessService, private router: Router) { 
    // this.emp_id = localStorage.getItem('loger_id');
    this.department_name = localStorage.getItem('department');

  }
  maxDate: string;;
  ngOnInit(): void {
     this.getLeaveTypes();
    this.getDepartmentsEmployee();
    // this.getDeptHandover();
    // this.getDeptalternateperson();
    // this.emp_id = localStorage.getItem('loger_id');
    this.department_name = localStorage.getItem('department');
   // this.getDeptHandover();
    this.getDeptByManagers();


    this.setMaxDate();
  }
  setMaxDate(): void {
    const date = new Date();
    const year = date.getFullYear();
    const month = date.getMonth() + 1; // JavaScript months are 0-based.
    const lastDay = new Date(year, month, 0).getDate();
    this.maxDate = `${year}-${month < 10 ? '0' + month : month}-${lastDay}`;
  }

  leavetypes;

  getLeaveTypes() {
    this.leavetypes =[];
    this.service.get('hr/leavepolicy.php?type=getLeaveTypesEmployee').subscribe(response => {
      this.leavetypes = response;
    });
  }


  calculateDays() {
    // if (this.leave_from && this.leave_to) {
    //   const start = new Date(this.leave_from);
    //   const end = new Date(this.leave_from);
    //   // const end = new Date(this.leave_to);
  
    //   let totalDays = 0;
  
    //   for (let current = start; current <= end; current.setDate(current.getDate() + 1)) {
    //     if (current.getDay() !== 0) { // Check if it's not Sunday
    //       totalDays++;
    //     }
    //   }
  
    //   // this.no_day = totalDays;
    //   } else {
    //     this.no_day = null;
    //     }
      this.no_day = 1;


    if(this.no_day >this.selected_emp['bal_see_OFF']){
      alertify.error('No of Days is Greater than C OFF Leaves');
    }
  }
  


  getDepartmentsEmployee() {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + localStorage.getItem('department')).subscribe(response => {
      this.employees1 = response;
    });
  }


  

  getDeptByManagers() {
    this.service.get('employee.php?type=getdeptHead&department_name=' + localStorage.getItem('department')).subscribe(response => {
      this.employees = response;
    });
    this.service.get('employee.php?type=getDeptHandover&department_name=' + localStorage.getItem('department')).subscribe(response => {
      this.handovers = response;
    });
    // let temp = [];
    // temp['emp_id'] = this.charge_handover['emp_id'];
    this.service.get('employee.php?type=getDeptalternateperson&department_name=' + localStorage.getItem('department') +'&empid='+this.emp_id ).subscribe(response => {
      this.persons = response;
    });
  }


  empdate;
  bleave =false;
  bleave1;
  selected_emp=[];
  emp_old: string;
  getDeptHandover(index) {
    this.selected_emp=this.employees1[index-1];
    this.selected_emp['joining_date']='2022-06-01'
    const currentDate = new Date();

    // Convert joining_date string to Date object
    const joiningDate = new Date(this.selected_emp['joining_date']);

    // Calculate the difference in milliseconds between the current date and the joining date
    const differenceInMilliseconds = currentDate.getTime() - joiningDate.getTime();

    // Convert milliseconds to months (approximate calculation)
    const differenceInMonths = differenceInMilliseconds / (1000 * 60 * 60 * 24 * 30.44); // Average number of days in a month

    // Check if the difference is more than or equal to 6 months
    if (differenceInMonths >= 6) {
      this.emp_old = 'Yes';
    } else {
      this.emp_old = 'No';
    }
    console.log(this.selected_emp);
    console.log('this.emp_old :>> ', this.emp_old);

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
     temp['department_name'] = this.department_name;
     temp['leave_to'] = this.leave_from;
     temp['leave_type'] = 'C OFF';
    this.service.post('hr/leaveForm.php?type=saveLeaveForm_coff', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        // this.router.navigate(['/employee-dashboard/leave_status'])
                                      
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }
    
}
