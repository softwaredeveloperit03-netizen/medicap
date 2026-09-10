import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review1',
  templateUrl: './review1.component.html',
  styleUrls: ['./review1.component.css']
})
export class Review1Component implements OnInit {

  isView = false;
  results;
  qa_comment='';

  selectedDev = [];
  incident_extension='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReview();
  }

  getPendingReview() {
    this.service.get('qms/incident.php?type=getActiveIncidents').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save(data) {
    this.service.post('qms/incident.php?type=saveDeptActivity&incident_no='+this.selectedDev['incident_no'],JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getPendingReview();
        this.isView = false;
        alertify.success('Incident Updated Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
