import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.service.get('qa/risk.php?type=getPendingAnalysis').subscribe(response => {
      this.results = response;
    });
  }
  analysis=[]
  isReView=false;
  viewRisk(index) {
    this.selectedRisk = this.results[index];

   
    
 
     
    

    this.getLikelihoodRisk();
    if(this.selectedRisk['analysis']=='pending'){
      this.isView = true;
      let assessment = this.selectedRisk["assessment_details"];
      this.assessment = assessment[0];
  console.log("this.assessment =", this.assessment);
    }else{
      let analysis = this.selectedRisk["analysis_details"];
    this.analysis = analysis[0];
console.log("this.analysis =", this.analysis);
      this.isReView = true;
    } 
   }
  
  getLikelihoodRisk() {
    this.service.get('qa/risk.php?type=getLikelihoodRisk').subscribe(response => {
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
  //  this.justification = '';
  let detail={}
        detail['name'] = this.risk['name'];
        detail['description'] = this.risk['description'];
        detail['score'] = this.risk['score'];
        detail['justification'] = this.justification;

    // this.selectedRisk["details"] = details;
    this.service.post('qa/risk.php?type=saveAnalysis&id=' + this.selectedRisk["id"], JSON.stringify(detail)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.isView = false;
        this.getPendingAnalysis();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveanaysisreview(status) {
 
    this.service.post('qa/risk.php?type=saveanaysisreview&id=' + this.selectedRisk["id"]+'&status='+status, JSON.stringify(this.selectedRisk["details"])).subscribe(response => {
     if (response['status'] == 'success') {
       alert('Saved Successfully');

       this.isReView = false;
       this.getPendingAnalysis();
     } else {
       alert('Failed: An error occured, please try again!');
     }
   });
 }
}
