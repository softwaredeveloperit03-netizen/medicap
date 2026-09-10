import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  reports;
  selectedReview = [];
  isView = false;

  comment = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingReviewMeetings();
  }

  getPendingReviewMeetings() {
    this.service.get('qa.php?type=getPendingReviewMeetings').subscribe(response => {
      this.reports = response;
    });
  }

  viewReviews(index) {
    this.selectedReview = this.reports[index];
    this.isView = true;
  }

  saveMeetingReview() {
    this.service.get('qa.php?type=saveManagementMeetingReview&comment=' + this.comment).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Review Saved Successfully');
        this.isView = false;
        this.getPendingReviewMeetings();
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
