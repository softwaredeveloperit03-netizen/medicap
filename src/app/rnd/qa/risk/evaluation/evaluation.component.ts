import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-evaluation',
  templateUrl: './evaluation.component.html',
  styleUrls: ['./evaluation.component.css']
})
export class EvaluationComponent implements OnInit {

  isView = false;
  results;
  selectedRisk = [];
  assessment = [];
  analysis = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingEvaluation();
  }

  getPendingEvaluation() {
    this.service.get('rnd/qa/risk.php?type=getPendingEvaluation').subscribe(response => {
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
      }
    }
    
    this.getMitigationRisk();
    this.isView = true;
  }

  getMitigationRisk() {
    this.service.get('rnd/qa/risk.php?type=getMitigationRisk').subscribe(response => {
      this.risks = response;
    });
  }

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveEvaluation() {
    let details = this.selectedRisk["details"];
    for (let i = 0; i < details.length; i++) {
      let detail = details[i];
      if (detail['type'] == "evaluation") {
        detail['name'] = this.risk['name'];
        detail['description'] = this.risk['description'];
        detail['score'] = this.risk['score'];
        detail['justification'] = this.justification;
        this.justification = '';
        details[i] = detail;
        break;
      }
    }
    this.selectedRisk["details"] = details;
    this.service.post('rnd/qa/risk.php?type=saveEvaluation&id=' + this.selectedRisk["id"], JSON.stringify(this.selectedRisk["details"])).subscribe(response => {
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
