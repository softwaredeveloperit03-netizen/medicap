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

  selectedResult = [];
  departments = [{"department_name": "Production", "status": false},
    {"department_name": "RND", "status": false},
    {"department_name": "Quality Control", "status": false},
    {"department_name": "Packing", "status": false},
    {"department_name": "Store", "status": false}];
  assessment = [];
  analysis = [];
  evaluation = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRiskReviews();
  }

  getPendingRiskReviews() {
    this.service.get('rnd/qa/risk.php?type=getPendingRiskReviews').subscribe(response => {
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

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    let list = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status'] == true) {
        list[list.length] = department;
      }
    }
    temp['departments'] = this.departments;
    this.service.post('rnd/qa/risk.php?type=saveRiskReview', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Saved Successfully');
        this.isView = false;
        this.getPendingRiskReviews();
        this.departments =  [{"department_name": "Production", "status": false},
        {"department_name": "RND", "status": false},
        {"department_name": "Quality Control", "status": false},
        {"department_name": "Packing", "status": false},
        {"department_name": "Store", "status": false}];
      }
    });
  }

}
