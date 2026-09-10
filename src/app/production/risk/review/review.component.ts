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

  selectedResult = [];
  assessment = [];
  analysis = [];
  evaluation = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDeptRiskReviews();
  }

  getPendingDeptRiskReviews() {
    this.service.get('qa/risk.php?type=getPendingDeptRiskReviews').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    let details = this.selectedResult["details"];
    for (let i = 0; i < details.length; i++) {
      let detail = details[i];
      if (detail['type'] == "assessment") {
        this.assessment = detail;
      } else if (detail['type'] == "analysis") {
        this.analysis = detail;
      } else if (detail['type'] == "evaluation") {
        this.evaluation = detail;
      }
    }
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alert('Comment / Remark is Required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    this.service.post('qa/risk.php?type=saveDeptRiskReview', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record saved successfully');
        this.isView = false;
        this.getPendingDeptRiskReviews();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
