import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-working',
  templateUrl: './working.component.html',
  styleUrls: ['./working.component.css']
})
export class WorkingComponent implements OnInit {
  loading;
  employees;
  employee;
  departments;
  department='';;
  fromdate;
  todate;
  designations;
  isProceed = false;
  designation='';
  selectedCountry=[];
  department_name='';
  results;
  from_date='';
  to_date='';
  columns;
  emp_code='';
  today= '';
  validateBtnState;
  employeeList;

  currentDate = new Date().toISOString().split("T")[0];

  constructor(private service: DataAccessService) {

   }

  ngOnInit() {
    this.getDepartments();
    this.getDetails();
    this.getDesignation();
    this.getIndividualAttendace();
    this.getEmployees();
  }

  getDetails() {
    this.service.get('management.php?type=getDetailsEmployee&department='+ this.department +'&designation='+this.designation).subscribe(response => {
      this.employees = response;
    });
  }

 

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getDesignation() {
    this.service.get('common.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  getDepartmentsEmployee(value) {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=' + value).subscribe(response => {
      this.employees = response;
    });
  }

  getDepartmentByEmployee(empId: any) {
    const obj = this.employeeList.find((item) =>item.emp_id == empId);
    console.log('')
    if(obj && obj.emp_id) {
      this.department_name = obj.department;
    }
  }
  
  getEmployees() {
    this.service.get('hr/task.php?type=getEmployees').subscribe((response: any) => {
      this.employeeList = response;
    });
  }

  getIndividualAttendace() {
    //  this.validateBtnState = ClrLoadingState.LOADING;
      this.service.get('hr/attendance.php?type=getIndividualAttendace&department_name=' + this.department_name + '&emp_code=' + this.emp_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
        this.employees = response;
       // this.validateBtnState = ClrLoadingState.DEFAULT;
        let temp = this.employees[0];
        this.columns = Object.keys(temp);
      });
    }

}
