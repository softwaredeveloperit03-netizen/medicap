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
    this.service.get('PchangeControl.php?type=get_CC_ForConsentAndReview&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.result = response;
      });
  }
  selectedResult = [];
SpecifyDetail
descriptionProposedChange
presentProcedure
rationalChange
rationalChangeFile
initiateDepartment
initiateDate
prodMatStageDoc
changeControl_no
batch_no
ChangeRetaedto: any=[];
  view(i){

    this.selectedResult = this.result[i];
    this.isView = true;
    
this.changeControl_no=this.selectedResult['changeControl_no']
this.initiateDepartment=this.selectedResult['initiateDepartment']
this.initiateDate=this.selectedResult['initiateDate'] 
this.SpecifyDetail=this.selectedResult['SpecifyDetail']
this.prodMatStageDoc=this.selectedResult['prodMatStageDoc']
this.batch_no=this.selectedResult['batch_no']
this.descriptionProposedChange=this.selectedResult['descriptionProposedChange'] 
this.presentProcedure=this.selectedResult['presentProcedure']
this.rationalChange=this.selectedResult['rationalChange']
this.rationalChangeFile=this.selectedResult['rationalChangeFile']
this.ChangeRetaedto=this.selectedResult['ChangeRetaedto']

  }


  viewDevDoc(url) {
     url = this.service.url + '../../upload/changeControl/pritam/' + url;
    window.open(url, '_blank');
  }


 
 
hod_cmt;
 saveDeviation() {
let temp={}

  temp['hod_cmt']=this.hod_cmt;
  temp['id']=this.selectedResult['id'];

  this.service.post('PchangeControl.php?type=Update_CC_ForConsentAndReview', JSON.stringify(temp)).subscribe(
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
