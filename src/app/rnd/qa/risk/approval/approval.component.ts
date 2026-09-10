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
    this.getCheckedRisks();
  }

  getCheckedRisks() {
    this.service.get('rnd/qa/risk.php?type=getCheckedRisks').subscribe(response => {
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

  update(status, data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['status'] = status;
    this.service.post('rnd/qa/risk.php?type=closeRisk', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Risk Record updated successfully');
        this.isView = false;
        this.getCheckedRisks();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
