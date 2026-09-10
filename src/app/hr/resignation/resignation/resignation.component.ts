import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { ChangeDetectorRef } from '@angular/core';
 

@Component({
  selector: 'app-resignation',
  templateUrl: './resignation.component.html',
  styleUrls: ['./resignation.component.css']
})
export class ResignationComponent implements OnInit {

  expltr= false;
  exinter= false;
  resigacc= false;
  selectedResult= [];
  resignations;
  checkListData;
  curr =false;
  last =false;
  

  constructor(private service: DataAccessService, private router: Router, private cdr : ChangeDetectorRef) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getData();
    this.getCheckListData();
    this.get_rights();
  }
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
       )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }



  getData() {
    this.service.get('hr/resignation.php?type=get_resignation').subscribe(response => {
      this.resignations = response;
    });
  }

  getCheckListData(){
    this.service.get('master/checklist.php?type=getExitInterviewcheckList').subscribe(response => {
      this.checkListData = response;
    });
  }

  salary_details;
  salary_details1;
  currInhand=0;
  lastInhand=0;
  getsalaryDetails(){

    this.currInhand =0;

    this.service.get('hr/resignation.php?type=getsalaryDetails&emp_id11='+this.selectedResult['emp_id']+'&expected_releaving='+this.selectedResult['expected_releaving']).subscribe(response => {
      this.salary_details = response;
      this.currInhand = response[0]?.inhand;
    });
  }
  getsalaryDetails1(){

    this.lastInhand =0;

    this.service.get('hr/resignation.php?type=getEmployeePriviousMonthSalary&emp_id11='+this.selectedResult['emp_id']+'&expected_releaving='+this.selectedResult['expected_releaving']).subscribe(response => {
      this.salary_details1 = response;
      this.lastInhand = response[0]?.inhand;

    });
  }

  checkListData1 =[];


  expLetter(index) {
    this.selectedResult = this.resignations[index];
    this.expltr = true;
    this.getsalaryDetails();
    this.getsalaryDetails1();
    this.cdr.detectChanges();
  }
  
  exitInterview(index) {
    this.selectedResult = this.resignations[index];
    this.exinter = true;
     this.cdr.detectChanges();
  }

  resignationAcceptance(index) {
    this.selectedResult = this.resignations[index];
    this.resigacc = true;

    
  }

  TotalPendingSal =0;
  salCalc(){
    if(this.last == true && this.curr == false){
      this.TotalPendingSal =0;
      this.TotalPendingSal =  Number(this.lastInhand);
    }
    else if(this.last == false && this.curr == true){
      this.TotalPendingSal =0;
      this.TotalPendingSal = Number(this.currInhand) ;
    }
    else if(this.last == true && this.curr == true){
      this.TotalPendingSal =0;
      this.TotalPendingSal = Number(this.currInhand) + Number(this.lastInhand);
    }else{
      this.TotalPendingSal =0;
    }
  }


  
  submitResignation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    
    let temp= data.value;
    this.service.post('hr/resignation.php?type=submitResignationReport&id='+this.selectedResult['id'], JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert("Data Submit Successfully !!");
        data.reset();
        this.resigacc = false;
        this.getData();
      } else {
        alert('An error occured');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }


  saveExitInterview() {
    let temp = {};
    temp['checkListData'] = this.checkListData;
    this.service.post('hr/resignation.php?type=saveExitInterview&id='+this.selectedResult['id'], JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Interview Completed');
        this.exinter = false;
        this.getData();
      } else {
        alert(response['status']);
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }


  key_skill ='';
  responsibilities ='';


  generateletter(Form){
    if(this.key_skill == '' && this.responsibilities == ''){
      alertify.error('All fields are required');
      return;
    }

    let temp ={};
    temp['key_skill'] = this.key_skill;
    temp['responsibilities'] = this.responsibilities;
    temp['currInhand'] = this.currInhand;
    temp['lastInhand'] = this.lastInhand;
    temp['TotalPendingSal'] = this.TotalPendingSal;
    temp['emp_name'] = this.selectedResult['emp_name'];
    temp['joining_date'] = this.selectedResult['joining_date'];
    temp['expected_releaving'] = this.selectedResult['expected_releaving'];
    temp['resignation_date'] = this.selectedResult['resignation_date'];
    temp['emp_id'] = this.selectedResult['emp_id'];
    temp['id'] = this.selectedResult['id'];

 
    this.service.post('hr/resignation.php?type=generateexpletter',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']==='success'){
         alertify.success('data save Successfuly');
         this.getData();
         this.expltr = false;
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }



 

  downloadPDF(id){
    this.service.open('hrDepartment.php?type=printexpletter&id='+id)
  }







}
