import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-evaluate2',
  templateUrl: './evaluate2.component.html',
  styleUrls: ['./evaluate2.component.css']
})
export class Evaluate2Component implements OnInit {
  isView = false;
  results;
  comment='';

  selectedDev = [];
  incident_extension='';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReview();
  }

  getPendingReview() {
    this.service.get('qms/incident.php?type=getPendingEvaluatation2').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save() {
    this.service.get('qms/incident.php?type=evaluate2Checking&incident_no='+this.selectedDev['incident_no']+'&qa_comment='+this.comment).subscribe(response => {
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
