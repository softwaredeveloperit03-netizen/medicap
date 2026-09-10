import {  Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-final-hr-round',
  templateUrl: './final-hr-round.component.html',
  styleUrls: ['./final-hr-round.component.css']
})
export class FinalHrRoundComponent implements OnInit {

  isView = false;
  isNew = false;
  

 

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCandidate();
    this.getDepartments();
  }

  
  results;
  getCandidate() {
    this.service.get('hr/candidate.php?type=getCandidateForInterviewFinalHrROund').subscribe((response) => {
        this.results = response;
    });
  }
 

    departments;
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation').subscribe((response) => {
        this.departments = response;
    });
  }


  employees;
  getEmployeesbyDept() {
    this.service.get('hr/candidate.php?type=getEmployeeByDeptWithCTC&depart='+this.department).subscribe((response) => {
        this.employees = response;
    });
  }
  

  isScheduleInterview = false;
  selectedCandidate = [];

  scheduleInterview(){
    this.isScheduleInterview = true;
  }


  isInterviewAllocated = false;
 
  allocateInterviewer(){
    this.isInterviewAllocated = true;
  }

  isPrimaryRoundHr = false;
 
  primaryRoundHr(){
    this.isPrimaryRoundHr = true;
  }


  isTechnicalRound = false;
 
  viewTechnicalDetails(){
    this.isTechnicalRound = true;
  }

  viewData(data){
    this.selectedCandidate = data;
    this.isNew = true;
  }

  isFinalRoundHr = false;
  department = '';
  ProceedFianlRound(data){
    this.selectedCandidate = data;
    this.isFinalRoundHr = true;
    this.department = this.selectedCandidate['department'];
    this.interviewChecklistByType();
    this.getEmployeesbyDept();
  }
 

  checklistData;
  interviewChecklistByType(){
    this.service.get('hr/candidate.php?type=interviewChecklistByType&checklistType=Final Round HR').subscribe(Response=>{
      this.checklistData=Response;
    })
  }



  decision = '';
  experinced = '';
  privious_ctc = null;

  replacement = 'No';
  criteria_sal = 10;
  repCtcAnuually = 0;
  repCtcMonthly = 0;
  expected_salary = null;
  recommended_salary = 0;
  offered_salary = 0;
  print_sal_val = 0;

  getRespDetails(i){
    let ind = i - 1;
    this.repCtcAnuually = this.employees[ind]?.ctcAnuually || 0;
    this.repCtcMonthly = this.employees[ind]?.ctcMonthly || 0;
  }

  jadu = false;
  calculateUpToSalary(){
 
  const tenPercent = (+this.repCtcAnuually * +this.criteria_sal) / 100;  // calculate 10%
  this.print_sal_val = +this.repCtcAnuually + +tenPercent;  
  
    if(this.recommended_salary > this.print_sal_val){
      this.jadu = true;
    }else{
      this.jadu = false;
    }
  }



 
  saveFinalHrRound(data,status) {

    if (!data.valid){
      alertify.error('Please Fill All Checklist.....');
      return;
    }
 
    let temp = data.value;
    temp['candidate_id'] = this.selectedCandidate['id'];
    temp['hrFinalChecklist'] = this.checklistData;
    temp['status'] = status;
  
    this.service.post('hr/candidate.php?type=saveFinalHrRound',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Candidate Interview Scedule Successfully');
          this.isFinalRoundHr = false;
          data.reset();
          this.getCandidate();
          this.selectedCandidate = [];
        } else {
          alertify.error('An error occured, please try again');
        }
      });
  }


 
  viewResume(url) {
    url = this.service.url + '../..' + url;
    window.open(url, '_blank');
  }
 

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
 
    
 
  
 
 



}

