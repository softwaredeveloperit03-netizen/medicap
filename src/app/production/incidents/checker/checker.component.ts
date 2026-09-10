import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
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
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingIncidents();
  }

  getPendingIncidents() {
    this.service.get('qa/incident.php?type=getPendingDeptIncidents').subscribe(response => {
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
