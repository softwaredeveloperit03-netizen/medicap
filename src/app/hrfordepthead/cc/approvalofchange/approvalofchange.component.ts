import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-approvalofchange',
  templateUrl: './approvalofchange.component.html',
  styleUrls: ['./approvalofchange.component.css']
})
export class ApprovalofchangeComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCcForApprovalOfChangeByQAHead();
  }


  typeCritical = '';
  typeMajor = '';

  results;
  isView = false;

  getCcForApprovalOfChangeByQAHead() {
    this.service
      .get(
        'changecontrol1.php?type=getCcQAByMeha&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  selectedResult = [];

  view(i){

    this.selectedResult = this.results[i];
    this.isView = true;
  }



  actionData =[];
  addAction(data){
    if(!data.valid){
      alertify.error('All Field Required!!!!');
      return;
    }

    let temp = data.value;
    this.actionData.push(temp);
    data.reset();
   }

   delAction(i){
    this.actionData.splice(i,1);
   }

 
  viewDevDoc(url) {
    url = this.service.url + '../../upload/changeControl/' + url;
   window.open(url, '_blank');
  }
 


  consentRevDoc: File;
  onFileChanged(event) {
   if (event.target.files.length === 1) {
     this.consentRevDoc = event.target.files[0];
   }
 }



 update(data) {

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

   const temp = data.value;
  
   temp['id'] = this.selectedResult['id'];
  temp['ccNo'] = this.selectedResult['ctrl_no'];
  temp['deptName'] = localStorage.getItem('department');
   
  this.service
    .post('changecontrol1.php?type=saveccQaHead', JSON.stringify(temp))
    .subscribe((response) => {
      if (response['status'] === 'success') {
        alert('Saved Successfully !!!!!!');
        this.getCcForApprovalOfChangeByQAHead();
        data.resetForm();
        this.isView = false;
        this.selectedResult = [];
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    });
}
 

}
