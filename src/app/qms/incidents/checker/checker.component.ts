import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checker',
  templateUrl: './checker.component.html',
  styleUrls: ['./checker.component.css'],
  providers: [DatePipe]
})
export class CheckerComponent implements OnInit {
  isView = false;
  incidents;
  selectedReport = [];

  comment_by_checker = '';
  incident = [];
  isApprover;
  isChecker;
  incident_date='';
  constructor(private service: DataAccessService, private datePipe:DatePipe) {
    this.incident_date = this.datePipe.transform(Date.now(), 'dd-MM-yyyy');
  }

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
    this.service.get('qa/incident.php?type=getPendingIncidents').subscribe(response => {
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
    this.service.post('qa/incident.php?type=updateIncident', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.comment_by_checker = '';
        this.isView = false;
        this.getPendingIncidents();
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
