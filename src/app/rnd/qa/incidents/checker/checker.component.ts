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
  results;

  selectedReport= [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingIncidents();
  }

  getPendingIncidents() {
    this.service.get('qa/incident.php?type=getPendingIncidents').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }


  updateIncident(status) {
    this.service.get('qa/incident.php?type=checkIncident&status=' + status + '&id=' + this.selectedReport['id'] + '&remark=' + this.remark).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Incident Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getPendingIncidents();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
