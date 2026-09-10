import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-assessment',
  templateUrl: './assessment.component.html',
  styleUrls: ['./assessment.component.css']
})
export class AssessmentComponent implements OnInit {

  isReView = false;
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
    this.service.get('qa/risk.php?type=getPendingAssessment').subscribe(response => {
      this.results = response;
    });
  }

  getAssessmentLog() {
    this.service.get('qa/risk.php?type=getAssessmentLog').subscribe(response => {
      this.reports = response;
    });
  }
  assessment=[]
  analysis=[]
  viewRisk(index) {
    this.selectedRisk = this.results[index];
    this.getQuantitativeRisk();
    //  let details = this.selectedRisk["assessment_details"];
    
        

     if(this.selectedRisk['assessment']=='pending'){
      this.isView = true;
    }else{
      this.isReView = true;
      let rawDetails = this.selectedRisk["assessment_details"];
      this.assessment = rawDetails[0];
console.log("this.assessment =", this.assessment);
    }
  }

  getQuantitativeRisk() {
    this.service.get('qa/risk.php?type=getQuantitativeRisk').subscribe(response => {
      this.risks = response;
    });
  }

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveAssessment() {
    let details = this.selectedRisk["assessment_details"];
    // for (let i = 0; i < details.length; i++) {
    //   let detail = details[i];
    //   if (detail['type'] == "assessment") {
    //     detail['name'] = this.risk['name'];
    //     detail['description'] = this.risk['description'];
    //     detail['score'] = this.risk['score'];
    //     detail['justification'] = this.justification;
    //     details[i] = detail;
    //     break;
    //   }
    // }
    let detail={}
        detail['name'] = this.risk['name'];
        detail['description'] = this.risk['description'];
        detail['score'] = this.risk['score'];
        detail['justification'] = this.justification;

    this.selectedRisk["details"] = details;
    this.service.post('qa/risk.php?type=saveAssessment&id=' + this.selectedRisk["id"], JSON.stringify(detail)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.isView = false;
        this.getPendingAssessment();
        this.getAssessmentLog();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveassreview(status) {
 
    this.service.post('qa/risk.php?type=saveassreview&id=' + this.selectedRisk["id"]+'&status='+status, JSON.stringify(this.selectedRisk["details"])).subscribe(response => {
     if (response['status'] == 'success') {
       alert('Saved Successfully');

       this.isReView = false;
       this.getPendingAssessment();
     } else {
       alert('Failed: An error occured, please try again!');
     }
   });
 }

  viewLog(index) {
    this.selectedRisk = this.reports[index];
    this.details = this.selectedRisk['details'];
    this.isLog = true;
  }

}
