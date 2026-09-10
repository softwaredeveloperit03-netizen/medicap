import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-deviationassassment',
  templateUrl: './deviationassassment.component.html',
  styleUrls: ['./deviationassassment.component.css']
})
export class DeviationassassmentComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviatiogetDeviationForConsernHoadAfterCapa();
   }


  result;
  isView = false;
 
  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

  getDeviatiogetDeviationForConsernHoadAfterCapa() {
    this.service.get('deviation2.php?type=getDeviatiogetDeviationForConsernHoadAfterCapa&deptName='+localStorage.getItem('department')).subscribe((response) => {
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
 
  this.service.post('deviation2.php?type=saveDeviationConsernHodCommentAfterCapa', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('DeviationAssessment By QA Saved Successfully !!!!!!');
          this.getDeviatiogetDeviationForConsernHoadAfterCapa();
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
