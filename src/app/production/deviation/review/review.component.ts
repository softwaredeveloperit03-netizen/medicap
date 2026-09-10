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

  selectedDev = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingReview();
  }

  getPendingReview() {
    this.service.get('deviation.php?type=getPendingReview').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    this.service.get('deviation.php?type=saveReview&id=' + this.selectedDev['comm_no'] + '&comment=' + temp['comment'] + '&dev_no='+ this.selectedDev['dev_no']).subscribe(response => {
      if (response['status']) {
        alertify.success("Review Submitted Successfully");
        this.isView = false;
        this.getPendingReview();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
