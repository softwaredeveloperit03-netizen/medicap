import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-review-qa',
  templateUrl: './review-qa.component.html',
  styleUrls: ['./review-qa.component.css'],
})
export class ReviewQaComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviatiogetDeviationForQareviewAfterCapa();
   }


  result;
  isView = false;
 
  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

  getDeviatiogetDeviationForQareviewAfterCapa() {
    this.service.get('deviation2.php?type=getDeviatiogetDeviationForQareviewAfterCapa&deptName='+localStorage.getItem('department')).subscribe((response) => {
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
 
  this.service.post('deviation2.php?type=saveDeviationQaReviewCapa', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('QA Reviewed Saved Successfully !!!!!!');
          this.getDeviatiogetDeviationForQareviewAfterCapa();
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
