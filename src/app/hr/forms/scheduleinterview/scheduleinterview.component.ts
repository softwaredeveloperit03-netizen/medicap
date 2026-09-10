import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
  selector: 'app-scheduleinterview',
  templateUrl: './scheduleinterview.component.html',
  styleUrls: ['./scheduleinterview.component.css']
})
export class ScheduleinterviewComponent implements OnInit {
  today;
  candidates;
  qualification;
  place;
  email_id;
  mobile_no;
  candidate_id;
  isInit = true;
  isFirst;
  departments;
  designations;
  employees;
  reqdesignation;
  interviewer = [];
  index = 0;
  constructor(private service: DataAccessService) {
    this.today = new Date().toLocaleDateString();
    let res = this.today.split('/');
    this.today = res[2] + '-' + res[1] + '-' + res[0];
   }

  ngOnInit() {
    this.getCandidates();
    this.getDepartments();
    this.getDesignation();
    this.getRequirementDesignation();
  }

  getCandidates() {
    this.service.get('hrDepartment.php?type=getPendingCandidates')
    .subscribe(response => {
      this.candidates = response;
    });
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

  saveForm(scheduleData) {
    let temp = scheduleData.value;
    temp["candidate_id"] = this.candidate_id;
    this.service.post('hrDepartment.php?type=scheduleInterview', JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        this.isFirst = true;
        this.isInit = false;
        scheduleData.reset();
        this.getCandidates();
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

  getCandidateInfo(value) {
    const index = value - 1;
    this.candidate_id = this.candidates[index].id;
    this.qualification = this.candidates[index].qualification;
    this.place = this.candidates[index].place;
    this.email_id = this.candidates[index].email_id;
    this.mobile_no = this.candidates[index].mobile_no;
  }

  addInterviewer(formdata) {
    this.interviewer[this.index] = formdata.value;
    this.index++;
    formdata.reset();
  }

  submitInterviewers() {
    this.service.post('hrDepartment.php?type=addInterviewers' + '&candidate_id=' + this.candidate_id, JSON.stringify(this.interviewer))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.isFirst = false;
        this.isInit = true;
        this.interviewer = [];
        this.getCandidates();
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
