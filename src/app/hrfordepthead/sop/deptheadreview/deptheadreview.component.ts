import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deptheadreview',
  templateUrl: './deptheadreview.component.html',
  styleUrls: ['./deptheadreview.component.css']
})
export class DeptheadreviewComponent implements OnInit {

  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {
    this.getPendingSopForDeptHeadReview();
  }

  result;
  isView = false;
  reUpload = false;
  selectedResult;

  getPendingSopForDeptHeadReview() {
    this.service.get('sops.php?type=getPendingSopForDeptHeadReview&deptName='+localStorage.getItem('department')).subscribe((response: any) => {
      this.result = response;
     });
  }

  reUploadtrig(){
    this.reUpload = true;
  }

  view(i){
    this.selectedResult = this.result[i];
    this.isView = true;
    this.reUpload = false;
  }


  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url+'?v=1';
   window.open(url, '_blank');
 }



reviewSop(){

let temp ={};

  this.service.post('sops.php?type=saveReviewedBydeptHead&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Reviewed Successfully !!!!!!');
        this.getPendingSopForDeptHeadReview();
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
temp['reUploadFrom'] = 'Department Head';

  this.service.post('sops.php?type=saveReUploadReviewByDeptHead&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Send For Re-Upload Successfully !!!!!!');
        this.getPendingSopForDeptHeadReview();
         this.isView = false;
         this.reUpload = false;
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }




}
