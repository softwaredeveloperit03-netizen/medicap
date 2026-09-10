import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-employee-master',
  templateUrl: './employee-master.component.html',
  styleUrls: ['./employee-master.component.css']
})
export class EmployeeMasterComponent implements OnInit {
  isNewCandidate = false;
  candidates;
  qualifications;
  isView=false;

  isUser = false;
  isChecker = false;
  isApprover = false;

  isScheduleInterview = false;
  today;
  selectedCandidate = [];
  departments;
  reqdesignation;
  designations;
  employees;
  selectedReport=[];

  isInterviewerAllocated = false;
  interviewer = [];
  constructor(private service: DataAccessService) {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));

    this.today = new Date().toLocaleDateString();
    let res = this.today.split('/');
    this.today = res[2] + '-' + res[1] + '-' + res[0];
   }

  ngOnInit() {
    this.getCandidateList();
    this.getApprovedQualifications();
    this.getDepartments();
    this.getRequirementDesignation();
  }

  getCandidateList() {
    this.service.get('hrDepartment.php?type=getCandidatesDetails').subscribe(response => {
      this.candidates = response;
    });
  }

  view(index){
    this.selectedReport=this.candidates[index];
    this.isView=true;
  }
  getDepartments() {
    this.service.get('hrDepartment.php?type=getApprovedDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }

  getRequirementDesignation() {
    this.service.get('hrDepartment.php?type=getApprovedDesignations')
    .subscribe(response => {
      this.reqdesignation = response;
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

  getApprovedQualifications() {
    this.service.get('hrDepartment.php?type=getApprovedQualifications')
    .subscribe(response => {
      this.qualifications = response;
    });
  }

  saveForm(candidateData) {
    let temp = candidateData.value;
    if (temp["candidate_name"].length < 3) {
      alert("Invalid Candidate Name");
      const element1 = document.getElementById('candidate_name') as HTMLElement;
      element1.focus();
      return;
    }
    if (temp["qualification"] == '') {
      alert("Qualification is Required");
      const element1 = document.getElementById('qualification') as HTMLElement;
      element1.focus();
      return;
    }
    if (temp["mobile_no"].length < 10 || temp["mobile_no"].length > 13) {
      alert("Invalid Mobile No");
      const element1 = document.getElementById('mobile_no') as HTMLElement;
      element1.focus();
      return;
    }
    if (temp["email_id"].length < 5) {
      alert("Invalid Email ID");
      const element1 = document.getElementById('email_id') as HTMLElement;
      element1.focus();
      return;
    }
    if (temp["address"] == '') {
      alert("Address is Required");
      const element1 = document.getElementById('address') as HTMLElement;
      element1.focus();
      return;
    }
    this.service.post('hrDepartment.php?type=addCandiate', JSON.stringify(candidateData.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        candidateData.resetForm();
        this.isNewCandidate = false;
        alert('Candidate Registration has been successful');
        this.getCandidateList();
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

  scheduleInterview(index) {
    this.selectedCandidate = this.candidates[index];
    this.isScheduleInterview = true;
  }

  saveScheduleInterview(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('hrDepartment.php?type=saveScheduleInterview&candidate_id=' + this.selectedCandidate['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('saved Successfully');
        this.isScheduleInterview = false;
        data.resetForm();
        this.getCandidateList();
      } else {
        alert('An error occured, please try again');
      }
    });
  }

  allocateInterviewers(index) {
    this.selectedCandidate = this.candidates[index];
    this.isInterviewerAllocated = true;
  }

  addInterviewer(formdata) {
    this.interviewer[this.interviewer.length] = formdata.value;
    formdata.reset();
  }

  submitInterviewers() {
    if (this.interviewer.length == 0) {
      alert('At leat 1 interviwer is required');
      return;
    }
    this.service.post('hrDepartment.php?type=addInterviewers' + '&candidate_id=' + this.selectedCandidate['id'], JSON.stringify(this.interviewer))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.isInterviewerAllocated = false;
        this.interviewer = [];
        this.getCandidateList();
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

}
