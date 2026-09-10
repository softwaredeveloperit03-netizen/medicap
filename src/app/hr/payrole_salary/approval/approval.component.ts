 
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  employees:any = [];
  employees_list = [];
  from_month;
  isView = false;
  columns = [];
  columns_row = [];
  total_salary = '0.00';
  year: string;
  month: string;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    //this.getEmployeeMonthSalary();
  }

   getEmployeeMonthSalary() {
    [this.year, this.month] = this.from_month.split('-');
    this.total_salary = '0.00';
    this.columns = [];
    this.columns_row = [];
    this.employees = [];
    this.employees_list = [];
    this.service.get('hrDepartment.php?type=getEmployeeMonthSalary&month=' + this.from_month +'&status=No').subscribe(response => {
      this.employees = response;
      let temp = this.employees[0];

      this.columns.push('Sr. No');
      this.columns.push('Emp Id');
      this.columns.push('Emp Name');
      this.columns.push('Department');
      this.columns.push('Designation');
      this.columns.push('Month Days');
      this.columns.push('Present Days(ATT + LV)');
      this.columns.push('Absent Days');
      this.columns.push('Week Off');
      this.columns.push('Leaves');
      this.columns.push('Late Mark(Days Count)');
      this.columns.push('Days Attend');
    

      this.columns.push('PF');
      this.columns.push('TDS');
      this.columns.push('Monthly Loan Emi');
      this.columns.push('Adv Salary');
      this.columns.push('Deductions Total');
      this.columns.push('Net Salary');
      this.columns.push('Gross Salary');
      this.columns.push('Per Day');
      this.columns.push('Per Hour');
      this.columns.push('OT Hours of Month');
      // this.columns.push('Eligible OT Hours');
      this.columns.push('OT Value');
      this.columns.push('Take Home  + OT ');

      for (let j = 0; j < this.employees.length; j++) {
        let emp = this.employees[j];
        let obj = {
          "sr_no": j+1,
          "emp_id": emp["emp_id"],
          "emp_name": emp["emp_name"],
          "department": emp["department"],
          "designation": emp["designation"],
          "month_days": emp["month_days"],
          "present_days": emp["present_days"],
          "absent_days": emp["absent_days"],
          "weekly_off": emp["weekly_off"],
          "leaves_of_month": emp["leaves_of_month"],
          "total_latemark": emp["total_latemark"],
          "present_days2": emp["present_days2"],
        
        }
      
        obj["pfff"] = Number(emp["pfff"]).toFixed(2);
        obj["emp_tds"] = Number(emp["emp_tds"]).toFixed(2);
        obj["EMI"] = emp["EMI"];
        obj["adv_sal"] = 0;
        
      obj["deduction"] = (Number(emp["pfff"]) + Number(emp["empEsicMonthly"])).toFixed(2);

        obj["inhand"] = emp["inhand"];
      
        obj["earned_gross"] = emp["earned_gross"];
        obj["per_day_salary"]= Number(emp["inhand"] / emp["month_days"]).toFixed(2);
        if(emp["operator_category"]=='Staff'){
          obj["per_hour_salary"]= Number(obj["per_day_salary"] /8).toFixed(2);
        }else if(emp["operator_category"]=='Worker / Operator'){
          obj["per_hour_salary"]=  Number(obj["per_day_salary"] /12).toFixed(2);
        }
      
        let otHrs=Number(emp["total"])
        // obj["eligible_hrs_month"] =Number(emp["total"]).toFixed(2);
        if(otHrs >=0){
          obj["eligible_hrs_month"]=otHrs
        }else{
          obj["eligible_hrs_month"]=0;
        }
        // let otHrs_total=Number(emp["ot_total"])
        // obj["eligible_hrs_month"] =Number(emp["total"]).toFixed(2);
        if(Number(emp["ot_total"]) >=0){
          console.log('Number(emp["ot_total"]) :>> ', Number(emp["ot_total"]));
          console.log('otHrs_total :>> ', Number(emp["ot_total"]));
        }else{
          console.log('Number(emp["ot_total"]) :>> ', Number(emp["ot_total"]));
          console.log('otHrs_total :>> ', Number(emp["ot_total"]));
          emp["ot_total"]=0;
        }



        // obj["eligible_hrs_month"] =Number(emp["ot_total"]).toFixed(2);

       


let ot_hrs =  Number((emp["ot_total"]*obj["per_hour_salary"]*1.5)).toFixed(2);
        // obj["ot_value"] = 
        if(emp["ot_total"] >=0){
          obj["ot_value"]=ot_hrs
          console.log('ot_hrs :>> ', ot_hrs);
          console.log('ot_hrs :>> Hiiiiiiiiiiiii')
        }else{
          console.log('ot_hrs :>> ', ot_hrs);
          obj["ot_value"]=0;
          console.log('ot_hrs :>> Biiiiiiiiiiiii')

        }
        if(emp["present_days"] >=emp["working_days"]){
          obj["final_take_home_salary"] =Number(parseFloat(emp["take_home_salary"]) + parseFloat(obj["ot_value"] ) - parseFloat(obj["deduction"] )).toFixed(2);
     
        }else{
          obj["final_take_home_salary"] = Number(parseFloat(obj["per_day_salary"]) * (parseFloat(emp["present_days"] )+parseFloat(emp["weekly_off"] )) - parseFloat(obj["deduction"] )+ parseFloat(obj["ot_value"] )).toFixed(2);
       
          }
          
        
          if (Number(obj['final_take_home_salary']) > 0) {
            this.total_salary = Number(Number(this.total_salary) + Number(obj["final_take_home_salary"])).toFixed(2);
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
  saveSalaryIndividual(idx) {
    
    let month = this.from_month.split('-');
    let salary_info = this.employees_list[idx];
    let obj = {
      "emp_id": salary_info['emp_id'],
      "salary_info": salary_info,
      "month": month[1],
      "year": month[0]

    }


    this.service.post('hrDepartment.php?type=saveSalaryIndividual', JSON.stringify(obj))
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
  viewSalarySlip(idx) {
    this.service.open('hr/reports/slip_report.php?type=payslip&id=' + this.employees_list[idx]['salary_slip_id']);

  }
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
