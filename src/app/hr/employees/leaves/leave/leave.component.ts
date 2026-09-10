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
  no_day:number = 0;
  no_day0:number = 0;

  
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getLeaveTypes();
    // this.getDeptHandover();
    // this.getDeptalternateperson();
  }

  getDepartmentsEmployee(value) {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
      this.employees1 = response;
    });
  }

  leavetypes;

  getLeaveTypes() {
    this.leavetypes =[];
    this.service.get('hr/leavepolicy.php?type=getLeaveTypes').subscribe(response => {
      this.leavetypes = response;
    });
  }
  


  
  calculateDays() {
    this.getweekoff(this.leave_from,this.leave_to);
    if (this.leave_from && this.leave_to) {
      const start = new Date(this.leave_from);
      const end = new Date(this.leave_to);

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
    }else{
    let temp = data.value;
    this.pendingList[this.pendingList.length] = temp;
    data.resetForm();
    }

  }

  delData(index) {

    this.pendingList.splice(index, 1);

  }
 

    splitDateRangeByMonth(startDate, endDate) {
    let ranges = [];
    let start = new Date(startDate);  // Initialize start date
    let end = new Date(endDate);      // Initialize end date

    // Ensure the end date is inclusive
    end.setDate(end.getDate() + 1);

    while (start < end) {
        // Get the last day of the current month
        let lastDayOfMonth = new Date(start.getFullYear(), start.getMonth() + 1, 0);

        // Adjust the periodEnd to be the minimum of end date or last day of the month
        let periodEnd = new Date(Math.min(end.getTime() - 1, lastDayOfMonth.getTime()));

        // Push the range to the array
        ranges.push({
            leave_from: start.toISOString().split('T')[0],
            leave_to: periodEnd.toISOString().split('T')[0]
        });

        // Move the start to the first day of the next month
        start = new Date(periodEnd.getFullYear(), periodEnd.getMonth(), periodEnd.getDate() + 1);
    }

    return ranges;
}



  
  
  

  save(form) {
    if (!form.valid) {
      alertify.error('All fields are required');
      return;
    }

    let formData = form.value;
    formData['pendingList'] = this.pendingList;

    // let dateRanges = this.splitDateRangeByMonth(formData['leave_from'], formData['leave_to']);
 

    // console.log('dateRanges');
    // console.log(dateRanges);
    
    // let leaveDataArray = dateRanges.map(range => {
    //   return {
    //     plant_id: formData['plant_id'],
    //     department_name: formData['department_name'],
    //     emp_id: formData['emp_id'],
    //     leave_from: range.leave_from,
    //     leave_to: range.leave_to,
    //     no_day: (new Date(range.leave_to).getTime() - new Date(range.leave_from).getTime()) / (1000 * 3600 * 24) + 1,
    //     leave_type: formData['leave_type'],
    //     reason_leave: formData['reason_leave'],
    //     approver: formData['approver'],
    //     charge_handover: formData['charge_handover'],
    //     alternate_person: formData['alternate_person'],
    //     contact_no: formData['contact_no'],
    //     pendingList: JSON.stringify(formData['pendingList']),
    //     entry_by: formData['entry_by'],
    //     entry_date: formData['entry_date'],
    //     status: 'Pending_Dept_Head_Approval'
    //   };
    // });


  
    //console.log('Leave Data Array:', leaveDataArray);
    this.service.post('hr/leaveForm.php?type=saveLeaveForm', JSON.stringify(formData)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Records Inserted Successfully');
        form.resetForm();
        this.router.navigate(['/hr/employees/leaves']);
      } else {
        alertify.error('Failed: An error occurred, Please try again!');
      }
    });
  }


 




  // save(data) {
  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   temp['pendingList'] = this.pendingList;
  //   this.service.post('hr/leaveForm.php?type=saveLeaveForm', JSON.stringify(temp)).subscribe(response => {
  //     console.log('response',response);
  //     // if (response['status'] == 'success') {
  //       alertify.success('Record Inserted Successfully');
  //       data.resetForm();
  //       this.router.navigate(['/hr/employees/leaves'])
  //     // } else {
  //     //   alertify.error('Failed: An error occured, Please try again!');
  //     // }
  //   });
  // }
    
}
