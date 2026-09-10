import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { FormGroup, Validators } from '@angular/forms';
declare let alertify;




@Component({
  selector: 'app-appraisal',
  templateUrl: './appraisal.component.html',
  styleUrls: ['./appraisal.component.css']
})
export class AppraisalComponent implements OnInit {
  isView = false;
  isPassword = false;
  employees;
  emp_id = '';
  departments;
  designations;
 department_name='';
  department = '';
  designation = '';
  status = '';
  family_members =[];
  academics=[];
  employeement=[]
  length;
  amtList = [];
  qualiList = [];
  languages = [];
  selectedResult = [];
  documents = [];
  from_date: any;
  isedit: boolean;
  isScheduleInterview = false;
  selectedCandidate=[];
  candidates;




 
  reqdesignation;
  
  
  transports;
  interviews =[];
  item =[];
  today;
  results;
  mobile_no='';
 
  candidate_name='';
  constructor(private service: DataAccessService, private router: Router) {
   
  }
  

  ngOnInit() {
   
    this.service.observableDepartment.subscribe(response => {
      this.departments = response;
    });
    this.getEmployees();
   

    this.getPendingSchedules();
   
  }

  getEmployees() {
    this.service.get('hr/employee.php?type=getEmployeesList&department_name=' + this.department + '&designation=' + this.designation + '&status=' + this.status).subscribe(response => {
      this.employees = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    
    this.isView = true;
  }




 
  scheduleInterview(index) {
    this.selectedCandidate = this.candidates[index];
    this.isScheduleInterview = true;
  }
  getPendingSchedules(){
    this.service.get('hr/appraisalchecklist.php?type=get_dept_approve').subscribe((response : any)=>{
      this.results=response;

    });
  }


  



  
  saveScheduleInterview(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('hr/candidate.php?type=saveScheduleInterview&candidate_id=' + this.selectedCandidate['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isScheduleInterview = true;
        data.resetForm();
        this.getPendingSchedules();
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }

}
