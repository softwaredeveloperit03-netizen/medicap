import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  constructor(private service: DataAccessService, private router: Router) { }

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
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.get('deviation.php?type=saveReview&comment=' + temp['comment'] + '&dev_no='+ this.selectedDev['dev_no'] +temp['departments'] + '&dev_no='+ this.selectedDev['departments']).subscribe(response => {
      if (response['status']) {
        alertify.success("Review Submitted Successfully");
        this.router.navigate(['/qms/deviation/log']);
        this.getPendingReview();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
