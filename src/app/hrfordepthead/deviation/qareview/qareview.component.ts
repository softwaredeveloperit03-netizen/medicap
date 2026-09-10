import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-qareview',
  templateUrl: './qareview.component.html',
  styleUrls: ['./qareview.component.css']
})
export class QareviewComponent implements OnInit {



    engg = false;
    admin = false;
    production = false;
    ehs = false;
    qc = false;
    store = false;
    micro = false;
    it = false;
    hr = false;
    regulatory = false;
    qa = false;
    rnd = false;




  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationForQaReview();
    this.engg = false;
    this.admin = false;
    this.production = false;
    this.ehs = false;
    this.qc = false;
    this.store = false;
    this.micro = false;
    this.it = false;
    this.hr = false;
    this.regulatory = false;
    this.qa = false;
    this.rnd = false;
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

  getDeviationForQaReview() {
    this.service.get('deviation2.php?type=getDeviationForQaReview&deptName='+localStorage.getItem('department')).subscribe((response) => {
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


 
  this.service.post('deviation2.php?type=saveQaReviewMeha', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('QA Review Saved Successfully !!!!!!');
          this.getDeviationForQaReview();
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
