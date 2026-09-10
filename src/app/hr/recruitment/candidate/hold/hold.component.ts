import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-hold',
  templateUrl: './hold.component.html',
  styleUrls: ['./hold.component.css']
})
export class HoldComponent implements OnInit {
  results;
  isView=false;
  selectedReport=[];
  qualifications;
  isScheduleInterview = false;
  departments;
  reqdesignation;
  designations;
  employees;
  interviewer = [];
  isInterviewerAllocated: false;

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getHoldCandidates();
    this.getDepartments();
    this.getDesignation();
  }
  getHoldCandidates(){
    this.service.get('hr/candidate.php?type=getHoldCandidates').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }
  getDepartments() {
    this.service.get('hrDepartment.php?type=getApprovedDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }

  getDesignation() {
    this.service.get('hrDepartment.php?type=getApprovedDesignations')
    .subscribe(response => {
      this.designations = response;
    });
  }

  getEmployees(dept, designation) {
    this.service.get('hrDepartment.php?type=getDeptEmployees' + '&fromdept=' + dept + '&designation=' + designation)
    .subscribe(response => {
      this.employees = response;
    });
  }
  
  addInterviewer(formdata) {
    this.interviewer[this.interviewer.length] = formdata.value;
    formdata.reset();
  }

  getApprovedQualifications() {
    this.service.get('hrDepartment.php?type=getApprovedQualifications')
    .subscribe(response => {
      this.qualifications = response;
    });
  }

  submitInterviewers() {
    if (this.interviewer.length == 0) {
      alertify.error('At leat 1 interviwer is required');
      return;
    }
    this.service.post('hr/candidate.php?type=rescheduleInterview' + '&candidate_id=' + this.selectedReport['id'], JSON.stringify(this.interviewer))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.interviewer = [];
        this.router.navigate(['/recruitment']);
        alertify.success('Interviewer is selected');
      }
    },
    (error: Response) => {
      if (error.status === 400) {
        alertify.success('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }
  rejectCandidate(){
    this.service.get('hr/candidate.php?type=rejectCandidate&id='+ this.selectedReport['id']).subscribe(reponse=>{
      
  });
  }
}
