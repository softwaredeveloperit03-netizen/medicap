import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-qaheadreview',
  templateUrl: './qaheadreview.component.html',
  styleUrls: ['./qaheadreview.component.css']
})
export class QaheadreviewComponent implements OnInit {

  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {
    this.getPendingSopForQaReview();
  }

  result;
  isView = false;
  reUpload = false;
  selectedResult;

  getPendingSopForQaReview() {
    this.service.get('sops.php?type=getPendingSopForQaHeadReview&deptName='+localStorage.getItem('department')).subscribe((response: any) => {
      this.result = response;
     });
  }

  view(i){
    this.selectedResult = this.result[i];
    this.isView = true;
    this.reUpload = false;
  }

  reUploadtrig(){
    this.reUpload = true;
  }


  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url+'?v=1';
   window.open(url, '_blank');
 }



 reviewSop(){

let temp ={};

  this.service.post('sops.php?type=saveReviewedByQaHead&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Reviewed Successfully !!!!!!');
        this.getPendingSopForQaReview();
         this.isView = false;
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }


 
 SavereUpload(data){

  if(!data.valid){
    alert('Please Mention Re-Upload Comment !!!!!!');
    return;
  }

let temp = data.value;
temp['reUploadFrom'] = 'QA HEAD';

  this.service.post('sops.php?type=saveReUploadReviewByQaHead&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Send For Re-Upload Successfully !!!!!!');
        this.getPendingSopForQaReview();
         this.isView = false;
         this.reUpload = false;
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }




}
