import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-impactassimentreview',
  templateUrl: './impactassimentreview.component.html',
  styleUrls: ['./impactassimentreview.component.css'],
})
export class ImpactassimentreviewComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationForMonitoringFollowUp();
    this.gtQaEmployees();
   }
   employees;

  result;
  isView = false;
 
  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

  getDeviationForMonitoringFollowUp() {
    this.service.get('deviation2.php?type=getDeviationForMonitoringFollowUp&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.result = response;
      });
  }

  gtQaEmployees() {
    this.service.get('deviation2.php?type=gtQaEmployees&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.employees = response;
      });
  }
 
  selectedResult = [];

  view(i){

    this.selectedResult = this.result[i];
    this.isView = true;
  }


  viewDevDoc(url) {
     url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
  }


  empName = '';
  getRespPer(i){
    this.empName = this.employees[i-1].empName;
  }

  closureData =[];
  addClosure(data){
    if(!data.valid){
      alertify.error('All Field Required!!!!');
      return;
    }

    let temp = data.value;
    temp['empName'] = this.empName;

    this.closureData.push(temp);
    data.reset();
    this.empName = '';
 
   }

   delClosure(i){
    this.closureData.splice(i,1);
   }
  

 

 
 saveDeviation(data) {

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  const temp = data.value;
  temp['id'] = this.selectedResult['id'];
  temp['deviation_no'] = this.selectedResult['deviation_no'];
  temp['devOccuredDept'] = this.selectedResult['devOccuredDept'];
  temp['closureData'] = this.closureData;
 
  this.service.post('deviation2.php?type=saveDeviationMonitoringClosure', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getDeviationForMonitoringFollowUp();
          data.resetForm();
          this.isView = false;
          this.selectedResult =[];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      }
    );
}


 



}
