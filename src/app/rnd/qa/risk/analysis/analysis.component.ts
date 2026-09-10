import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-analysis',
  templateUrl: './analysis.component.html',
  styleUrls: ['./analysis.component.css']
})
export class AnalysisComponent implements OnInit {

  isView = false;
  results;
  selectedRisk = [];
  assessment = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingAnalysis();
  }

  getPendingAnalysis() {
    this.service.get('rnd/qa/risk.php?type=getPendingAnalysis').subscribe(response => {
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
        break;
      }
    }
    
    this.getLikelihoodRisk();
    this.isView = true;
  }

  getLikelihoodRisk() {
    this.service.get('rnd/qa/risk.php?type=getLikelihoodRisk').subscribe(response => {
      this.risks = response;
    });
  }

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveAnalysis() {
    this.risk['justification'] = this.justification;
    this.justification = '';
    let details = this.selectedRisk["details"];
    for (let i = 0; i < details.length; i++) {
      let detail = details[i];
      if (detail['type'] == "analysis") {
        detail['name'] = this.risk['name'];
        detail['description'] = this.risk['description'];
        detail['score'] = this.risk['score'];
        detail['justification'] = this.justification;
        details[i] = detail;
        break;
      }
    }
    this.selectedRisk["details"] = details;
    this.service.post('rnd/qa/risk.php?type=saveAnalysis&id=' + this.selectedRisk["id"], JSON.stringify(this.selectedRisk["details"])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingAnalysis();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
