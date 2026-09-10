import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-deptreview',
  templateUrl: './deptreview.component.html',
  styleUrls: ['./deptreview.component.css'],
})
export class DeptreviewComponent implements OnInit {
  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCCForDeptConsentAndReview();
  }


  results;
  isView = false;

  getCCForDeptConsentAndReview() {
    this.service
      .get(
        'changecontrol1.php?type=getCCForDeptMeha&deptName=' +
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

  let formData = new FormData();
  const temp = data.value;

  for (let key in temp) {
    if (temp.hasOwnProperty(key)) {
      formData.append(key, temp[key]);
    }
  }

   formData.append('id', this.selectedResult['id']);
  formData.append('ccNo', this.selectedResult['ctrl_no']);
  formData.append('deptName', localStorage.getItem('department'));
  
  if (this.consentRevDoc) {
    formData.append('consentRevDoc', this.consentRevDoc, this.consentRevDoc.name);
  }

  this.service
    .post('changecontrol1.php?type=saveDeptReviewMeha', formData)
    .subscribe((response) => {
      if (response['status'] === 'success') {
        alert('Consent And Reviewed Saved Successfully !!!!!!');
        this.getCCForDeptConsentAndReview();
        data.resetForm();
        this.isView = false;
        this.selectedResult = [];
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    });
}


 



}
