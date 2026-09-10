import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-initiate-log',
  templateUrl: './initiate-log.component.html',
  styleUrls: ['./initiate-log.component.css']
})
export class InitiateLogComponent implements OnInit {

  constructor(private service: DataAccessService,private router : Router) {  }

  ngOnInit(): void {
    this.getSopLog(localStorage.getItem('department'));
    this.department = localStorage.getItem('department');
    this.department_name = localStorage.getItem('department');
    this.getDepartments();
  }

  department = localStorage.getItem('department');
  department_name = localStorage.getItem('department');

  result;
  isCc = false;
  isRevision = false;
  selectedResult =[];

  getSopLog(department) {
    this.service.get('sops.php?type=getSopLog&deptName='+department).subscribe((response: any) => {
      this.result = response;
     });
  }

  ccAction(i){
    this.selectedResult = this.result[i];
    this.isCc = true;
    this.isRevision = false;
  }

  newRevView =false;
  newRev(i){
    this.selectedResult = this.result[i];
    this.newRevView = true;
  }

  
  revisionHistory =false;
  revHistory(i){
    this.selectedResult = this.result[i];
    this.revisionHistory = true;
  }

  revisionComment = '';

  revision(i){
    this.selectedResult = this.result[i];
    this.isRevision = true;
    this.revisionComment = '';
  }

  departments;
  particular = 'SOPs';

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }


  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url+'?v=1';
   window.open(url, '_blank');
 }



 saveChangeControlSop(data){

  if(!data.valid){
    alert('All Field Required !!!!!');
    return;
  }

  let temp = data.value;

  this.service.post('sops.php?type=saveChangeControlSop&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Saved Successfully !!!!!!');
        this.getSopLog(localStorage.getItem('department'));
         this.isCc = false;
          
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }
 SentForRevision(data){

  if(!data.valid){
    alert('All Field Required !!!!!');
    return;
  }

  let temp = data.value;

  this.service.post('sops.php?type=SentForRevision&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Saved Successfully !!!!!!');
        this.getSopLog(localStorage.getItem('department'));
         this.newRevView = false;
          
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }

 saveRequest(data){

  if(!data.valid){
    alertify.error('All Field Required!!!!');
    return;
  }

let temp = this.selectedResult;
temp['revisionComment'] = data.value.revisionComment;

console.log(temp);

  this.service.post('sops.php?type=saveSopRevisionRequest', JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Submit Successfully !!!!!!');
        this.getSopLog(localStorage.getItem('department'));
         this.isRevision = false;
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }



}
