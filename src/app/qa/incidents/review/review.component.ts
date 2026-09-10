import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {
  isView = false;
  results;
  qa_comment='';

  selectedDev = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReview();
  }

  getPendingReview() {
    this.service.get('qms/incident.php?type=getPendingReviewDept').subscribe(response => {
      this.results = response;
      
          });
  }

  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save() {
    this.service.get('qms/incident.php?type=saveReview&incident_no='+this.selectedDev['incident_no']+'&qa_comment='+this.qa_comment).subscribe(response => {
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
