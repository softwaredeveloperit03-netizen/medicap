import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
declare let alertify;
@Component({
  selector: 'app-payrole',
  templateUrl: './payrole.component.html',
  styleUrls: ['./payrole.component.css']
})
export class PayroleComponent implements OnInit {
  employees;
  employees_list = [];
  from_month;
  isView = false;
  columns = [];
  columns_row = [];
  total_salary = '0.00';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    //this.getEmployeeMonthSalary();
  }

  getEmployeeMonthSalary() {

    this.columns = [];
    this.columns_row = [];
    this.employees = [];
    this.employees_list = [];
    this.service.get('hrDepartment.php?type=getEmployeeMonthSalary&month=' + this.from_month).subscribe(response => {
      this.employees = response;
      let temp = this.employees[0];
      this.columns.push('Emp Id');
      this.columns.push('Emp Name');
      this.columns.push('Department');
      this.columns.push('Designation');
      this.columns.push('Working Days');
      this.columns.push('Absent Days');
      this.columns.push('Take Home Salary');
      this.columns.push('Status');
      let keys = Object.keys(temp['earningsList']);
      for (let i = 0; i < keys.length; i++) {
        this.columns.push(keys[i]);
      }
      this.columns.push('Earnings Total');
      keys = Object.keys(temp['deductions']);
      for (let i = 0; i < keys.length; i++) {
        this.columns.push(keys[i]);
      }

      this.columns.push('Deductions Total');
      this.columns.push('Net Salary');

      for (let j = 0; j < this.employees.length; j++) {
        let emp = this.employees[j];
        let obj = {
          "emp_id": emp["emp_id"],
          "emp_name": emp["emp_name"],
          "department": emp["department"],
          "designation": emp["designation"],
          "present_days": emp["present_days"],
          "absent_days": emp["absent_days"],
          "take_home_salary": emp["take_home_salary"],
          "salary_slip_id": emp["salary_slip_id"]
        }
        let keys = Object.keys(emp['earningsList']);
        let data = emp['earningsList'];
        for (let x = 0; x < keys.length; x++) {
          obj[keys[x]] = data[keys[x]];
        }
        keys = Object.keys(emp['deductions']);
        obj["earned_gross"] = emp["earned_gross"];
        data = emp['deductions'];
        for (let x = 0; x < keys.length; x++) {
          obj[keys[x]] = data[keys[x]];
        }

        obj["deduction"] = emp["deduction"];
        obj["inhand"] = emp["inhand"];

        if (Number(emp["inhand"]) > 0) {
          this.total_salary = parseFloat((Number(this.total_salary) + Number(emp["inhand"])) + '').toFixed(2);
        }
        //  obj["salary_slip_id"] = emp["salary_slip_id"] > 0 ? 'Approved' : 'Pending';
        this.employees_list.push(obj);
      }
      if (this.employees_list.length > 0) {
        this.columns_row = Object.keys(this.employees_list[0]);
      }

    });




  }
  saveSalary(idx) {
    let month = this.from_month.split('-');
    let salary_info = this.employees_list[idx];
    let obj = {
      "emp_id": salary_info['emp_id'],
      "salary_info": salary_info,
      "month": month[1],
      "year": month[0]

    }


    this.service.post('hrDepartment.php?type=save_employee_salary', JSON.stringify(obj))
      .subscribe(response => {
        if (response['status'] === 'success') {
          alertify.success('Approved Successfully');
          this.getEmployeeMonthSalary();
        } else {
          alertify.error(response['status']);
        }
      },
        (error: Response) => {
          if (error.status === 400) {
            alertify.error('An error has occurred.');
          } else {
            alertify.error('An error has occurred, http status:' + error.status);
          }
        });

  }
  sendForClearance() {
    let month = this.from_month.split('-');
    let obj = {
      "month": month[1],
      "year": month[0]

    }


    this.service.post('hrDepartment.php?type=update_salary_clearance_info', JSON.stringify(obj))
      .subscribe(response => {
        if (response['status'] === 'success') {
          alertify.success('Approved Successfully');
          this.getEmployeeMonthSalary();
        } else {
          alertify.error(response['status']);
        }
      },
        (error: Response) => {
          if (error.status === 400) {
            alertify.error('An error has occurred.');
          } else {
            alertify.error('An error has occurred, http status:' + error.status);
          }
        });

  }
  reportpayrole() {
    window.location.href = this.service.url + 'reports/salary.php';
  }
  viewSalarySlip(idx) {
    this.service.open('hr/reports/slip_report.php?type=payslip&id=' + this.employees_list[idx]['salary_slip_id']);

  }
  //hr/report/slip_report.php?type=salary-slipformate&id=36
}
