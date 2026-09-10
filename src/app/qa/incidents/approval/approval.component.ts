import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
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
    this.service.get('qms/incident.php?type=getPendingExtensionVerifications').subscribe(response => {
      this.results = response;
    });
  }

  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  update(status) {
    this.service.get('qms/incident.php?type=verifyExtension&incident_no='+this.selectedDev['incident_no']+'&status='+status).subscribe(response => {
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
