import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-initiate-checking',
  templateUrl: './initiate-checking.component.html',
  styleUrls: ['./initiate-checking.component.css']
})
export class InitiateCheckingComponent implements OnInit {

  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {
    this.getPendingSopForQaReview();
  }

  result;
  isView = false;
  reUpload = false;
  selectedResult;

  getPendingSopForQaReview() {
    this.service.get('sops.php?type=getPendingSopForQaReview&deptName='+localStorage.getItem('department')).subscribe((response: any) => {
      this.result = response;
     });
  }

  view(i){
    this.selectedResult = this.result[i];
    this.isView = true;
    this.reUpload = false;

  }


  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url +'?v=1';
   window.open(url, '_blank');
 }

 reUploadtrig(){
  this.reUpload = true;
}

 reviewSop(){

let temp ={};

  this.service.post('sops.php?type=saveReviewedByQa&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
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
temp['reUploadFrom'] = 'QA Department';

  this.service.post('sops.php?type=saveReUploadReviewByQa&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
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
