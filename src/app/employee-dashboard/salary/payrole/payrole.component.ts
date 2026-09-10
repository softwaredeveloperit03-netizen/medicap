import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
declare let alertify;

@Component({
  selector: 'app-payrole',
  templateUrl: './payrole.component.html',
  styleUrls: ['./payrole.component.css']
})
export class PayroleComponent implements OnInit {

//   employees;
//   employees_list = [];
//   from_month;
//   isView = false;
//   columns = [];
//   columns_row = [];
//   total_salary = '0.00';
//   constructor(private service: DataAccessService) { }

//   ngOnInit() {
//     //this.getEmployeeMonthSalary();
//   }

//   getEmployeeMonthSalary() {

//     this.columns = [];
//     this.columns_row = [];
//     this.employees = [];
//     this.employees_list = [];
//     this.service.get('hrDepartment.php?type=getEmployeeMonthSalary&month=' + this.from_month).subscribe(response => {
//       this.employees = response;
//       let temp = this.employees[0];
//       this.columns.push('Emp Id');
//       this.columns.push('Emp Name');
//       this.columns.push('Department');
//       this.columns.push('Designation');
//       this.columns.push('Working Days');
//       this.columns.push('Absent Days');
//       this.columns.push('Take Home Salary');
//       this.columns.push('Status');
//       let keys = Object.keys(temp['earningsList']);
//       for (let i = 0; i < keys.length; i++) {
//         this.columns.push(keys[i]);
//       }
//       this.columns.push('Earnings Total');
//       keys = Object.keys(temp['deductions']);
//       for (let i = 0; i < keys.length; i++) {
//         this.columns.push(keys[i]);
//       }

//       this.columns.push('Deductions Total');
//       this.columns.push('Net Salary');

//       for (let j = 0; j < this.employees.length; j++) {
//         let emp = this.employees[j];
//         let obj = {
//           "emp_id": emp["emp_id"],
//           "emp_name": emp["emp_name"],
//           "department": emp["department"],
//           "designation": emp["designation"],
//           "present_days": emp["present_days"],
//           "absent_days": emp["absent_days"],
//           "take_home_salary": emp["take_home_salary"],
//           "salary_slip_id": emp["salary_slip_id"]
//         }
//         let keys = Object.keys(emp['earningsList']);
//         let data = emp['earningsList'];
//         for (let x = 0; x < keys.length; x++) {
//           obj[keys[x]] = data[keys[x]];
//         }
//         keys = Object.keys(emp['deductions']);
//         obj["earned_gross"] = emp["earned_gross"];
//         data = emp['deductions'];
//         for (let x = 0; x < keys.length; x++) {
//           obj[keys[x]] = data[keys[x]];
//         }

//         obj["deduction"] = emp["deduction"];
//         obj["inhand"] = emp["inhand"];

//         if (Number(emp["inhand"]) > 0) {
//           this.total_salary = parseFloat((Number(this.total_salary) + Number(emp["inhand"])) + '').toFixed(2);
//         }
//         //  obj["salary_slip_id"] = emp["salary_slip_id"] > 0 ? 'Approved' : 'Pending';
//         this.employees_list.push(obj);
//       }
//       if (this.employees_list.length > 0) {
//         this.columns_row = Object.keys(this.employees_list[0]);
//       }

//     });




//   }
//   saveSalary(idx) {
//     let month = this.from_month.split('-');
//     let salary_info = this.employees_list[idx];
//     let obj = {
//       "emp_id": salary_info['emp_id'],
//       "salary_info": salary_info,
//       "month": month[1],
//       "year": month[0]

//     }


//     this.service.post('hrDepartment.php?type=save_employee_salary', JSON.stringify(obj))
//       .subscribe(response => {
//         if (response['status'] === 'success') {
//           alertify.success('Approved Successfully');
//           this.getEmployeeMonthSalary();
//         } else {
//           alertify.error(response['status']);
//         }
//       },
//         (error: Response) => {
//           if (error.status === 400) {
//             alertify.error('An error has occurred.');
//           } else {
//             alertify.error('An error has occurred, http status:' + error.status);
//           }
//         });

//   }
//   sendForClearance() {
//     let month = this.from_month.split('-');
//     let obj = {
//       "month": month[1],
//       "year": month[0]

//     }


//     this.service.post('hrDepartment.php?type=update_salary_clearance_info', JSON.stringify(obj))
//       .subscribe(response => {
//         if (response['status'] === 'success') {
//           alertify.success('Approved Successfully');
//           this.getEmployeeMonthSalary();
//         } else {
//           alertify.error(response['status']);
//         }
//       },
//         (error: Response) => {
//           if (error.status === 400) {
//             alertify.error('An error has occurred.');
//           } else {
//             alertify.error('An error has occurred, http status:' + error.status);
//           }
//         });

//   }
//   reportpayrole() {
//     window.location.href = this.service.url + 'reports/salary.php';
//   }
//   viewSalarySlip(idx) {
//     this.service.open('hr/reports/slip_report.php?type=payslip&id=' + this.employees_list[idx]['salary_slip_id']);

//   }
//   //hr/report/slip_report.php?type=salary-slipformate&id=36
// }


employees:any = [];
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
  
  this.total_salary = '0.00';
  this.columns = [];
  this.columns_row = [];
  this.employees = [];
  this.employees_list = [];
  this.service.get('hrDepartment.php?type=getEmployeeMonthSalary_by_emp&month=' + this.from_month +'&status=No').subscribe(response => {
    this.employees = response;
    let temp = this.employees[0];

    this.columns.push('Sr. No');
    this.columns.push('Emp Id');
    this.columns.push('Emp Name');
    this.columns.push('Department');
    this.columns.push('Designation');
    this.columns.push('Working Days');
    this.columns.push('Absent Days');
    this.columns.push('Take Home Salary');
    // this.columns.push('Status');
    // let keys = Object.keys(temp['earningsList']);
    // for (let i = 0; i < keys.length; i++) {
    //   this.columns.push(keys[i]);
    // }
    // this.columns.push('Earnings Total');
    // keys = Object.keys(temp['deductions']);
    // for (let i = 0; i < keys.length; i++) {
    //   this.columns.push(keys[i]);
    // }

    this.columns.push('Deductions Total');
    this.columns.push('Net Salary');
    this.columns.push('Gross Salary');
    this.columns.push('Per Day');
    this.columns.push('Monthly Loan Emi');
    this.columns.push('Per Hour');
    this.columns.push('OT Hours of Month');
    this.columns.push('Eligible OT Hours');
    // this.columns.push('OT Value');

    this.columns.push('take Home  + OT ');

    for (let j = 0; j < this.employees.length; j++) {
      let emp = this.employees[j];
      let obj = {
        "sr_no": j+1,
        "emp_id": emp["emp_id"],
        "emp_name": emp["emp_name"],
        "department": emp["department"],
        "designation": emp["designation"],
        "present_days": emp["present_days"],
        "absent_days": emp["absent_days"],
        "take_home_salary": Number(emp["take_home_salary"]).toFixed(2),
       
        // "salary_slip_id": emp["salary_slip_id"]
      }
      // let keys = Object.keys(emp['earningsList']);
      // let data = emp['earningsList'];
      // for (let x = 0; x < keys.length; x++) {
      //   obj[keys[x]] = data[keys[x]];
      // }
      // keys = Object.keys(emp['deductions']);
      // obj["earned_gross"] = emp["earned_gross"];
      // data = emp['deductions'];
      // for (let x = 0; x < keys.length; x++) {
      //   obj[keys[x]] = data[keys[x]];
      // }

      obj["deduction"] = emp["deduction"];
      obj["inhand"] = emp["inhand"];
    
      obj["earned_gross"] = emp["earned_gross"];
      obj["per_day_salary"]= Number(emp["inhand"] / emp["month_days"]).toFixed(2);
      obj["EMI"] = emp["EMI"];
      if(emp["operator_category"]=='Staff'){
        obj["per_hour_salary"]= Number(obj["per_day_salary"] /9).toFixed(2);
      }else if(emp["operator_category"]=='Worker / Operator'){
        obj["per_hour_salary"]=  Number(obj["per_day_salary"] /12).toFixed(2);
      }
      obj["ot_hrs_month"] = emp["total"];
      obj["eligible_hrs_month"] = emp["ot_total"];
      obj["ot_value"] =  Number(emp["ot_total"]*obj["per_hour_salary"]).toFixed(2);
      if(emp["present_days"] >=emp["working_days"]){
        obj["final_take_home_salary"] =Number(parseFloat(emp["take_home_salary"]) + parseFloat(obj["ot_value"] ) - parseFloat(obj["EMI"] )).toFixed(2);
    console.log('greater :>> ' );
      }else{
        obj["final_take_home_salary"] = Number(parseFloat(obj["per_day_salary"]) * parseFloat(emp["present_days"] ) - parseFloat(obj["EMI"] )).toFixed(2);
        console.log('lower :>> ' );
      }
     
     console.log('ot_value'+obj['ot_value'])
     console.log('final_take_home_salary'+obj['final_take_home_salary'])
      if (Number(emp["inhand"]) > 0) {
        this.total_salary = parseFloat((Number(this.total_salary) + Number(obj["final_take_home_salary"])) + '').toFixed(2);
      }
      //  obj["salary_slip_id"] = emp["salary_slip_id"] > 0 ? 'Approved' : 'Pending';
       this.employees_list.push(obj);
    }
    if (this.employees_list.length > 0) {
      this.columns_row = Object.keys(this.employees_list[0]);
    }

  });

//   this.columns = [];
//   this.columns_row = [];
//   // this.employees = [];
//   this.employees_list = [];
//   this.service.get('hrDepartment.php?type=getEmployeeMonthSalary&month=' + this.from_month).subscribe(response => {
//     this.employees = response;
//     console.log(this.employees)
//     let temp = this.employees[0];
//     console.log(temp)
//     this.columns.push('Emp Id');
//     this.columns.push('Emp Name');
//     this.columns.push('Department');
//     this.columns.push('Designation');
//     this.columns.push('Working Days');
//     this.columns.push('Absent Days');
//     this.columns.push('Take Home Salary');
//     this.columns.push('Total Deductions');
//     this.columns.push('Gross Salary');
 

//     // let keys = Object.keys(temp['earningsList']);
//     // for (let i = 0; i < keys.length; i++) {
//     //   this.columns.push(keys[i]);
//     // }
//     // this.columns.push('Earnings Total');
//     // keys = Object.keys(temp['deductions']);
//     // for (let i = 0; i < keys.length; i++) {
//     //   this.columns.push(keys[i]);
//     // }


//     for (let j = 0; j < this.employees.length; j++) {
//       let emp = this.employees[j];
//       let obj = {
//         "emp_id": emp["emp_id"],
//         "emp_name": emp["emp_name"],
//         "department": emp["department"],
//         "designation": emp["designation"],
//         "present_days": emp["working_days"],
//         "absent_days": emp["absent_days"],
//         "take_home_salary": emp["take_home_salary"],
//         "total_deductions": emp["deductions"],
//         "gross_salary": emp["gross_salary"],
//         // "salary_slip_id": emp["salary_slip_id"]
//       }
//       // let keys = Object.keys(emp['earningsList']);
//       // let data = emp['earningsList'];
//       // for (let x = 0; x < keys.length; x++) {
//       //   obj[keys[x]] = data[keys[x]];
//       // }
//       // keys = Object.keys(emp['deductions']);
//       // obj["earned_gross"] = emp["earned_gross"];
//       // data = emp['deductions'];
//       // for (let x = 0; x < keys.length; x++) {
//       //   obj[keys[x]] = data[keys[x]];
//       // }


//       if (Number(emp["inhand"]) > 0) {
//         this.total_salary = parseFloat((Number(this.total_salary) + Number(emp["inhand"])) + '').toFixed(2);
//       }
//       //  obj["salary_slip_id"] = emp["salary_slip_id"] > 0 ? 'Approved' : 'Pending';
//       this.employees_list.push(obj);
//     }
//     if (this.employees_list.length > 0) {
//       this.columns_row = Object.keys(this.employees_list[0]);
//     }

//   });




}

viewSalarySlip(id,half) {
  this.service.open('hr/reports/slip_report.php?type=payslip&id=' + id +'&month=' + this.from_month);

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


saveSalaryBulk(month,year) {

  console.log("in this house");
  // let month = this.from_month.split('-');
  // let salary_info = this.employees_list;
  // let obj = {
  //   "emp_id": salary_info['emp_id'],
  //   "salary_info": salary_info,
  //   "month": month[1],
  //   "year": month[0]

  // }
  let temp ={};
  temp['employees_list'] =this.employees_list;
  temp['month']=month;
  temp['year']=year;

  this.service.post('hrDepartment.php?type=save_employee_salary_Bulk&month1='+month[1]+'&year1='+month[0], JSON.stringify(temp))
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


let sal_month0= month[1];
let sal_month1= month[0];
  this.service.post('hrDepartment.php?type=update_salary_clearance_info&month1=' + this.from_month, JSON.stringify(obj))
    .subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Approved Successfully');
        this.saveSalaryBulk(sal_month0,sal_month1);
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
// viewSalarySlip(idx) {
//   this.service.open('hr/reports/slip_report.php?type=payslip&id=' + this.employees_list[idx]['salary_slip_id']);

// }
//hr/report/slip_report.php?type=salary-slipformate&id=36

exportToExcel(): void {
  const fileName = 'salary_or_payroll_approval.xlsx';

  // Construct the header row
  const header = this.columns;

  // Construct the data rows
  const data = [header, ...this.employees_list.map(emp => this.columns_row.map(col => emp[col]))];

  // Convert data to Excel worksheet
  const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);

  // Create a new workbook and append the worksheet
  const wb: XLSX.WorkBook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Salary or Payroll Approval');

  // Save the workbook to a file
  XLSX.writeFile(wb, fileName);
}
}
