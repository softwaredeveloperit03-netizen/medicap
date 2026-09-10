import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { FormBuilder } from '@angular/forms';
import { DatePipe } from '@angular/common';
declare let alertify;


@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe]
})
export class NewComponent implements OnInit {

   isView = false;
  results;
  isNew = false;
  departments;
  reqdesignation;
  designations;
  employees;
  resume: File;
ngForm: any;
designation: any;
department: any;
type: any;
  today: string;
  amt=0;
no_month=0;
// emi=0;
minDate='';
   

  constructor(private service: DataAccessService,private router: Router,private formBuilder: FormBuilder, public fb: FormBuilder, private datePipe: DatePipe) {
    this.minDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = new Date().toLocaleDateString();
    let res = this.today.split('/');
    this.today = res[2] + '-' + res[1] + '-' + res[0];
   }
  

  ngOnInit() {
    this.getDepartments();
    this.getEmployeesss();
  }






















  principal: number;
  rate = 11.40;
  emi: number;
  emiStartDate: Date;
  months: number;
  amortizationSchedule: { month: number, emiStartDate: Date, principal: number, interest: number }[] = [];

  calculateMonths(data) {


    if (!data.valid){
      alertify.error('All FIeld Required');
      return false;
    }

    const roi = this.rate / 12 / 100; // Monthly interest rate

    const n = Math.log(this.emi / (this.emi - this.principal * roi)) / Math.log(1 + roi);
  
    this.months = Math.ceil(n);


    
    this.calculateMonths1();
  }   




firstEmiDate ;
lastEmiDate ;
no_of_emi =0;

  calculateMonths1() {

    this. isSchedule = false;
    const roi = this.rate / 12 / 100; // Monthly interest rate

    let remainingPrincipal = this.principal;
    this.amortizationSchedule = []; // Reset the schedule array

    let currentDate = new Date(this.emiStartDate); // Start date for EMI

    for (let i = 1; i <= this.months; i++) {
      const interest = remainingPrincipal * roi;
      const principalPayment = this.emi - interest;

      this.amortizationSchedule.push({
        month: i,
        emiStartDate: new Date(currentDate), // Create a new Date object for each month
        principal: principalPayment,
        interest: interest
      });

      remainingPrincipal -= principalPayment;

      currentDate.setMonth(currentDate.getMonth() + 1);
      
    }


   this.no_of_emi =  this.amortizationSchedule.length;

    this.firstEmiDate  = this.amortizationSchedule[0]?.emiStartDate;
    this.lastEmiDate  = this.amortizationSchedule[this.no_of_emi - 1 ]?.emiStartDate;

 


  }

  isSchedule = false;

  viewSchedule(){
    this.isSchedule = true;

  }

 
  
  calc_emi(){
    this.emi = Number((this.amt / this.no_month).toFixed(2));
    console.log(this.emi)
}

  getDepartments(){
    // this.service.observableDepartment.subscribe(response => {
    //   this.departments = response;
    // }); ;
    this.service.get('hr/employee.php?type=get_department_by_designation')
    .subscribe(response => {
      this.departments = response;
    });

  }
  getDesignation(department) {
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }
  getDesignation1(data) {
    let department = data.value;
    for (let i=0; i< this.departments.length;i++){
      if(this.departments[i]['department_name'] == department){
        this.designations = this.departments[i]['designations'];
      }
    } 
  }
  sel_loan_dtl=[];
  emp_loans;


  selectedEmployee =[];

  getEmployees_Loan_dtl(value,index){
    this.selectedEmployee =[];
     this.service.get('hr/loan.php?type=get_save_emp_loan&employee=' +value)
    .subscribe(response => {
      this.emp_loans = response;
    });

    this.selectedEmployee = this.employees[index];
  }






  number(value){
    if (isNaN(value)){
      alertify.error('Number 10 digit Only');
      return false;
    }
  }
  onFileChanged(event) {
    if (event.target.files.length !== 0) {
      this.resume = event.target.files[0];
    }
  }


  getEmployees(dept, designation) {
    this.service.get('hr/employee.php?type=getDepartmentEmployees' + '&department_name=' + dept + '&designation=' + designation)
    .subscribe(response => {
      //this.employees = response;
    });
  }


  getEmployeesss() {
    this.service.get('hr/employee.php?type=getEmployeesss')
    .subscribe(response => {
      this.employees = response;
    });
  }







  from_date;
  employ;

  save_emp_loan(data) {
    let temp=data.value;
    //  temp['amtp']=this.amt;
    //  temp['no_monthp']=this.no_month;
    //  temp['emip']=this.emi;
     temp['principal']=this.principal;
     temp['rate']=this.rate;
     temp['emi']=this.emi;
     temp['emiStartDate']=this.emiStartDate;
      temp['no_month']=this.months;
     temp['emi_schedules']=this.amortizationSchedule;

    this.service.post('hr/loan.php?type=save_emp_loan&emp=' + this.employ, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        alertify.success("Load Added Successfully");
        data.reset();
       } else{
        alertify.error("Error Occure!!!!!!!");
      }
    });
   
  }



  isNumber(value,id){

    if(isNaN(value)){


      if(id == 'principal'){

        this.principal = 0;

      }else if (id == 'emi'){
        this.emi = 0;
      }

      alertify.error("Please Enter Numeric Value!!!!");

    }
  }









}
