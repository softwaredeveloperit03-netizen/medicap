import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-officereview',
  templateUrl: './officereview.component.html',
  styleUrls: ['./officereview.component.css']
})
export class OfficereviewComponent implements OnInit {

  
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
    this.service.get('PchangeControl.php?type=get_CC_ForConsentAndReview_For_QA_Office&deptName='+localStorage.getItem('department')).subscribe((response) => {
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
ChangeRetaedto:any=[]
prodMatStageDoc
changeControl_no
batch_no
reqDocuments = [
  { id: 1, name: 'Training', value: 1, valuen: false },
  { id: 2, name: 'Cleaning & Sanitization', value: 2, valuen: false },
  { id: 3, name: 'Change in Document', value: 3, valuen: false },
  { id: 4, name: 'Change in Specification/STP', value: 4, valuen: false },
  { id: 5, name: 'Validation (Process/ Cleaning)', value: 5, valuen: false },
  { id: 6, name: 'layout', value: 6, valuen: false },
  { id: 7, name: 'Qualification', value: 7, valuen: false },
  { id: 7, name: 'Requalification', value: 7, valuen: false },
  { id: 7, name: 'MRP List', value: 7, valuen: false },
  { id: 8, name: 'Others', value: 8, valuen: false }
];
onDepartmentSelect(selectedDept: any, event: any) {
  selectedDept.valuen = event.target.checked; // Toggle true/false for only clicked item
  console.log(this.reqDocuments); // full array with updated true/false
}
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
this.hod_cmt=this.selectedResult['hod_cmt']

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
