import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationForConsentAndReview();
  }


  result;
  isView = false;
  searchText = '';

  get filteredResults() {
    if (!this.result || !this.searchText || !this.searchText.trim()) {
      return this.result || [];
    }
    const q = this.searchText.trim().toLowerCase();
    return this.result.filter(
      (r) =>
        (r.deviation_no && String(r.deviation_no).toLowerCase().includes(q)) ||
        (r.devOccuredDate && String(r.devOccuredDate).toLowerCase().includes(q)) ||
        (r.devOccuredDept && String(r.devOccuredDept).toLowerCase().includes(q)) ||
        (r.DeviationType && String(r.DeviationType).toLowerCase().includes(q)) ||
        (r.identifiedBy && String(r.identifiedBy).toLowerCase().includes(q))
    );
  }

  getDeviationForConsentAndReview() {
    this.service.get('deviation2.php?type=getDeviationForConsentAndReview&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.result = response;
      });
  }
  selectedResult = [];

  view(i) {
    const list = this.filteredResults;
    this.selectedResult = list[i];
    this.isView = true;
  }


  viewDevDoc(url) {
     url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
  }


  consentRevDoc: File;
  onFileChanged(event) {
   if (event.target.files.length === 1) {
     this.consentRevDoc = event.target.files[0];
   }
 }

 justForDeviation = '';

 saveDeviation(data) {

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

  formData.append('deviationID', this.selectedResult['id']);
  formData.append('id', this.selectedResult['id']);
  formData.append('deviationNo', this.selectedResult['deviation_no']);
  formData.append('deptName', localStorage.getItem('department'));
  
  if (this.consentRevDoc) {
    formData.append('consentRevDoc', this.consentRevDoc, this.consentRevDoc.name);
  }

  this.service
    .post('deviation2.php?type=saveDeptConcentAndReviewMeha', formData)
    .subscribe((response) => {
      if (response['status'] === 'success') {
        alert('Consent And Reviewed Saved Successfully !!!!!!');
        this.getDeviationForConsentAndReview();
        data.resetForm();
        this.isView = false;
        this.selectedResult = [];
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    });
}


 



}
