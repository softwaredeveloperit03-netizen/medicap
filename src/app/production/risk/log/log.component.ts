import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

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
    this.getDeptRiskLog();
  }

  getDeptRiskLog() {
    this.service.get('qa/risk.php?type=getDeptRiskLog').subscribe(response => {
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

}
