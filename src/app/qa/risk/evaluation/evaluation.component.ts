import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.service.get('qa/risk.php?type=getPendingEvaluation').subscribe(response => {
      this.results = response;
    });
  }
  evaluation=[]
  viewRisk(index) {
    this.selectedRisk = this.results[index];

    let assessment = this.selectedRisk["assessment_details"];
      this.assessment = assessment[0];
  console.log("this.assessment =", this.assessment);

  let analysis = this.selectedRisk["analysis_details"];
  this.analysis = analysis[0];
console.log("this.analysis =", this.analysis);
    

    this.getMitigationRisk();
    if(this.selectedRisk['evaluation']=='pending'){
      this.isView = true;
     
    }else{
      this.isReView = true;
      let evaluation = this.selectedRisk["evaluation_details"];
      this.evaluation = evaluation[0];
  console.log("this.evaluation =", this.evaluation);
    } 
   }
   isReView=false;
  getMitigationRisk() {
    this.service.get('qa/risk.php?type=getMitigationRisk').subscribe(response => {
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
    let detail={}
    detail['name'] = this.risk['name'];
    detail['description'] = this.risk['description'];
    detail['score'] = this.risk['score'];
    detail['justification'] = this.justification;
    this.service.post('qa/risk.php?type=saveEvaluation&id=' + this.selectedRisk["id"], JSON.stringify(detail)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.isView = false;
        this.getPendingEvaluation();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveEvaluationreview(status) {
 
    this.service.post('qa/risk.php?type=saveEvaluationreview&id=' + this.selectedRisk["id"]+'&status='+status, JSON.stringify(this.selectedRisk["details"])).subscribe(response => {
     if (response['status'] == 'success') {
       alert('Saved Successfully');

       this.isReView = false;
       this.getPendingEvaluation();
     } else {
       alert('Failed: An error occured, please try again!');
     }
   });
 }
}
