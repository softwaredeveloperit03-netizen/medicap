import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checker',
  templateUrl: './checker.component.html',
  styleUrls: ['./checker.component.css']
})
export class CheckerComponent implements OnInit {
  isView = false;
  incidents;
  selectedReport = [];

  comment_by_checker = '';
  incident = [];
  isApprover;
  isChecker;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingIncidents();

    if(localStorage.getItem('approver') == 'true') {
      this.isApprover =  true;
    } else {
      this.isApprover =  false;
    }

    if(localStorage.getItem('checker') == 'true') {
      this.isChecker =  true;
    } else {
      this.isChecker =  false;
    }

  }

  getPendingIncidents() {
    this.service.get('qa/incident.php?type=getIncident').subscribe(response => {
      this.incidents = response;
    });
  }

  viewIncident(index) {
    this.selectedReport = this.incidents[index];
    this.isView = true;
  }


  updateIncident(status) {
    let temp = {};
    temp['comment_by_checker'] = this.comment_by_checker;
    temp['status'] = status;
    temp['incident_no'] = this.selectedReport['incident_no'];
    this.service.post('qa/incident.php?type=updateIncidentByChecker', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.comment_by_checker = '';
        this.isView = false;
        this.getPendingIncidents();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
