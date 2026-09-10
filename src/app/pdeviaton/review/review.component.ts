import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationForConsentAndReview();
       
  }
 
 departments: any = [];
impactData: any[] = [];
selectedDepartment: string = '';
remarks: string = '';
name: string = '';
date: string = '';

 
 


 
  result;
  isView = false;

  getDeviationForConsentAndReview() {
    this.service.get('pDeviation.php?type=getDeviationForConsentAndReview_For_QA_Manager&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.result = response;
      });
  }
  selectedResult = [];
initiateDepartment
initiateDate
prodMatStageDoc
batch_no
mfg_date
exp_date
deviation_type
actualProcedure
deviationObserved
rootCause
rootCauseFile
RiskAssessment
ActionTaken
impactingDepartments:any[]=[]
  view(i){

    this.selectedResult = this.result[i];
    this.isView = true;
    this.initiateDepartment=this.selectedResult['initiateDepartment']
    this.initiateDate=this.selectedResult['initiateDate']
    this.prodMatStageDoc=this.selectedResult['prodMatStageDoc']
    this.batch_no=this.selectedResult['batch_no']
    this.mfg_date=this.selectedResult['mfg_date']
    this.exp_date=this.selectedResult['exp_date']
    this.deviation_type=this.selectedResult['deviation_type']
    this.actualProcedure=this.selectedResult['actualProcedure']
    this.deviationObserved=this.selectedResult['deviationObserved']
    this.rootCause=this.selectedResult['rootCause']
    this.rootCauseFile=this.selectedResult['rootCauseFile']
    this.RiskAssessment=this.selectedResult['RiskAssessment']
    this.ActionTaken=this.selectedResult['ActionTaken']
    this.impactingDepartments=this.selectedResult['impactDeparments']
  }


  viewDevDoc(url) {
     url = this.service.url + '../../upload/deviation/pritam/' + url;
    window.open(url, '_blank');
  }


 
 
QA_manager_cmts='';
 saveDeviation() {
let temp={}

  temp['QA_manager_cmts']=this.QA_manager_cmts;
  temp['id']=this.selectedResult['id'];

  this.service.post('pDeviation.php?type=Update_QA_manager_cmts_Deviation', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Consent And Reviewed Saved Successfully !!!!!!');
          this.getDeviationForConsentAndReview();
         
          this.isView = false;
          this.selectedResult =[];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      }
    );
}


 



}
