import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-rootcause',
  templateUrl: './rootcause.component.html',
  styleUrls: ['./rootcause.component.css']
})
export class RootcauseComponent implements OnInit {
 
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationForCapa();
    this.gtQaEmployees();
  }


  result;
  isView = false;
  employees;

  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

  getDeviationForCapa() {
    this.service.get('deviation2.php?type=getDeviationForCapa&deptName='+localStorage.getItem('department')).subscribe((response) => {
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

  capaData =[];
  addCapa(data){
    if(!data.valid){
      alertify.error('All Field Required!!!!');
      return;
    }

    let temp = data.value;
    temp['empName'] = this.empName;

    this.capaData.push(temp);
    data.reset();
    this.empName = '';
 
   }

   delCapa(i){
    this.capaData.splice(i,1);
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
  temp['capaData'] = this.capaData;

  this.service.post('deviation2.php?type=saveDeviationcapa', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getDeviationForCapa();
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
