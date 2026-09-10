import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaitingreview',
  templateUrl: './awaitingreview.component.html',
  styleUrls: ['./awaitingreview.component.css']
})
export class AwaitingreviewComponent implements OnInit {

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
  remark='';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingEvaluation();
  }

  getPendingEvaluation() {
    this.service.get('qa/risk.php?type=getPendingDepHeadReview&dep_name='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  viewRisk(index) {
    this.selectedRisk = this.results[index];
    let assessment = this.selectedRisk["assessment_details"];
    this.assessment = assessment[0];
console.log("this.assessment =", this.assessment);

let analysis = this.selectedRisk["analysis_details"];
this.analysis = analysis[0];
console.log("this.analysis =", this.analysis);
let evaluation = this.selectedRisk["evaluation_details"];
    this.evaluation = evaluation[0];
console.log("this.evaluation =", this.evaluation);
      
  
     
        this.isView = true;
    } 
  

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveControl(status) {
    let temp={}
   
    temp['status']=status;
    temp['risk_cmt_id']=this.selectedRisk["risk_cmt_id"];
    this.service.post('qa/risk.php?type=saveReviewFromDEpHEAD&id=' + this.selectedRisk["id"],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.isView = false;
        this.getPendingEvaluation();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
