import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FormBuilder } from '@angular/forms';
import { DatePipe } from '@angular/common';
declare let alertify;
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
  providers: [DatePipe]
})
export class ApprovalComponent implements OnInit {
  software_type='';
  plant_id='';


  constructor(private service: DataAccessService,private router: Router,private formBuilder: FormBuilder, public fb: FormBuilder, private datePipe: DatePipe) {
    this.software_type = this.service.getPlantConfigFields('software_type');
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    console.log(this.software_type)
   }

  ngOnInit(): void {
    this.getEmployees_Loan_dtl()
  }
  results;
  
  getEmployees_Loan_dtl(){
    this.service.get('hr/loan.php?type=get_save_emp_loan_log')
   .subscribe(response => {
     this.results = response;
   });
 }

 isContainer=false;
 selected_emp=[];
 view(index){
  this.selected_emp=this.results[index];
  this.isContainer=true;
 }


 save_emp_loan(status) {
  let temp={};
  temp['status']=status
  temp['id']=this.selected_emp['id']

 

  this.service.post('hr/loan.php?type=update_save_emp_loan', JSON.stringify(temp)).subscribe(response => {
    if(response['status']=='success') {
      alertify.success(response['msg']);
      this.isContainer=false;
      this.getEmployees_Loan_dtl()
     } else{
      alertify.error(response['msg']);
    }
  });
 
}




download(id) {
  this.service.open('hr/reports/slip_report.php?type=loan_application&id=' +id);
}
exportToExcel(): void {
  const formattedData = this.results.map((result, index) => ({
    'Employee Name': result.firstname,
    'Loan Amount': result.loan_amt,
    'Number of Month': result.total_month,
    'EMI Amount': result.monthly_emi,
    'Installment Start From': result.emi_start_from,
    'Installment End': result.emi_end,
    'Status': result.status
  }));

  const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(formattedData);
  const workbook: XLSX.WorkBook = {
    Sheets: { 'data': worksheet },
    SheetNames: ['data']
  };

  XLSX.writeFile(workbook, 'LoanData.xlsx');
}
}
