import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-closure',
  templateUrl: './closure.component.html',
  styleUrls: ['./closure.component.css']
})
export class ClosureComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationFOrClosure();
     
   }
   capaImple = '';

  result;
  isView = false;
 
  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

  getDeviationFOrClosure() {
    this.service.get('deviation2.php?type=getDeviationFOrClosure&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.result = response;
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


 

  
 saveDeviation(data) {

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  const temp = data.value;
  temp['id'] = this.selectedResult['id'];
  temp['deviation_no'] = this.selectedResult['deviation_no'];
  temp['devOccuredDept'] = this.selectedResult['devOccuredDept'];
  
  this.service.post('deviation2.php?type=saveDeviationClosure', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getDeviationFOrClosure();
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
