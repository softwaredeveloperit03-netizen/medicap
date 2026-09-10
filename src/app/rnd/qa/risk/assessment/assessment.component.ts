import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-assessment',
  templateUrl: './assessment.component.html',
  styleUrls: ['./assessment.component.css']
})
export class AssessmentComponent implements OnInit {

  isView = false;
  results;
  selectedRisk = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';

  isLog = false;
  reports;
  details = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingAssessment();
    this.getAssessmentLog();
  }

  getPendingAssessment() {
    this.service.get('rnd/qa/risk.php?type=getPendingAssessment').subscribe(response => {
      this.results = response;
    });
  }

  getAssessmentLog() {
    this.service.get('rnd/qa/risk.php?type=getAssessmentLog').subscribe(response => {
      this.reports = response;
    });
  }

  viewRisk(index) {
    this.selectedRisk = this.results[index];
    this.getQuantitativeRisk();
    this.isView = true;
  }

  getQuantitativeRisk() {
    this.service.get('rnd/qa/risk.php?type=getQuantitativeRisk').subscribe(response => {
      this.risks = response;
    });
  }

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveAssessment() {
    let details = this.selectedRisk["details"];
    for (let i = 0; i < details.length; i++) {
      let detail = details[i];
      if (detail['type'] == "assessment") {
        detail['name'] = this.risk['name'];
        detail['description'] = this.risk['description'];
        detail['score'] = this.risk['score'];
        detail['justification'] = this.justification;
        details[i] = detail;
        break;
      }
    }
    this.selectedRisk["details"] = details;
    this.service.post('rnd/qa/risk.php?type=saveAssessment&id=' + this.selectedRisk["id"], JSON.stringify(this.selectedRisk["details"])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingAssessment();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewLog(index) {
    this.selectedRisk = this.reports[index];
    this.details = this.selectedRisk['details'];
    this.isLog = true;
  }

}
