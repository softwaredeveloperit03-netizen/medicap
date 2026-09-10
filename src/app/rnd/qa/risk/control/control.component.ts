import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-control',
  templateUrl: './control.component.html',
  styleUrls: ['./control.component.css']
})
export class ControlComponent implements OnInit {

  isView = false;
  results;
  selectedRisk = [];
  assessment = [];
  analysis = [];
  evaluation = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingEvaluation();
  }

  getPendingEvaluation() {
    this.service.get('rnd/qa/risk.php?type=getPendingControl').subscribe(response => {
      this.results = response;
    });
  }

  viewRisk(index) {
    this.selectedRisk = this.results[index];

    let details = this.selectedRisk["details"];
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

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveControl() {
    this.service.get('rnd/qa/risk.php?type=saveControl&id=' + this.selectedRisk["id"]).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingEvaluation();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
