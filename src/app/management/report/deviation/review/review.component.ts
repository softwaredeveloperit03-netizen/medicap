import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.service.get('management/deviation.php?type=getPendingReview' ).subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alert('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    this.service.get('deviation.php?type=saveReview&id=' + this.selectedDev['comm_no'] + '&comment=' + temp['comment'] + '&dev_no='+ this.selectedDev['dev_no']).subscribe(response => {
      if (response['status']) {
        alert("Review Submitted Successfully");
        this.isView = false;
        this.getPendingReview();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
