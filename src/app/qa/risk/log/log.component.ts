import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
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

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingEvaluation();
  }

  getPendingEvaluation() {
    this.service.get('qa/risk.php?type=getRiskLog').subscribe((response) => {
      this.results = response;
    });
  }

  download() {
    this.service.open('qa/risk.php?type=downloadLog&id='+ this.selectedRisk['id']);
  }

  viewRisk(index) {
    this.selectedRisk = this.results[index];

    // let details = this.selectedRisk["details"];
    // for (let i = 0; i < details.length; i++) {
    //   let detail = details[i];
    //   if (detail['type'] == "assessment") {
    //     this.assessment = detail;
    //   } else if (detail['type'] == "analysis") {
    //     this.analysis = detail;
    //   } else if (detail['type'] == "evaluation") {
    //     this.evaluation = detail;
    //   }
    // }
    let assessment = this.selectedRisk['assessment_details'];
    this.assessment = assessment[0];
    console.log('this.assessment =', this.assessment);

    let analysis = this.selectedRisk['analysis_details'];
    this.analysis = analysis[0];
    console.log('this.analysis =', this.analysis);

    let evaluation = this.selectedRisk['evaluation_details'];
    this.evaluation = evaluation[0];
    console.log('this.evaluation =', this.evaluation);
    this.isView = true;
  }
}
