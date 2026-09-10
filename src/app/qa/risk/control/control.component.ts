import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.service.get('qa/risk.php?type=getPendingControl').subscribe(response => {
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

  saveControl() {
    this.service.get('qa/risk.php?type=saveControl&id=' + this.selectedRisk["id"]).subscribe(response => {
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
