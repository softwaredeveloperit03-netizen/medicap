import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;


@Component({
  selector: 'app-externalagency',
  templateUrl: './externalagency.component.html',
  styleUrls: ['./externalagency.component.css']
})
export class ExternalagencyComponent implements OnInit {

  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationFoeExternalAgency();
  }


  result;
  isView = false;
  searchText = '';
  deviationApproval = '';

  externalRevAndApproval = '';

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

  getDeviationFoeExternalAgency() {
    this.service.get('deviation2.php?type=getDeviationFoeExternalAgency&deptName='+localStorage.getItem('department')).subscribe((response) => {
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


 

 
 saveDeviation(data) {

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  const temp = data.value;
  temp['id'] = this.selectedResult['id'];

  this.service
    .post(
      'deviation2.php?type=saveExternalAnegcyCommentMeha',
      JSON.stringify(temp)
    )
    .subscribe((response) => {
      if (response['status'] === 'success') {
        alert('Review And Comment Saved Successfully !!!!!!');
        this.getDeviationFoeExternalAgency();
        data.resetForm();
        this.isView = false;
        this.selectedResult = [];
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    });
}


 



}
