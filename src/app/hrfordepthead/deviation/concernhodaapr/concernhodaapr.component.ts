import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-concernhodaapr',
  templateUrl: './concernhodaapr.component.html',
  styleUrls: ['./concernhodaapr.component.css']
})
export class ConcernhodaaprComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationConcernApprovalData();
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

  getDeviationConcernApprovalData() {
    this.service.get('deviation2.php?type=getDeviationConcernApprovalData&deptName='+localStorage.getItem('department')).subscribe((response) => {
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


  immActtionnDoc: File;
  onFileChanged(event) {
   if (event.target.files.length === 1) {
     this.immActtionnDoc = event.target.files[0];
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

  formData.append('id', this.selectedResult['id']);
  
  if (this.immActtionnDoc) {
    formData.append('immActtionnDoc', this.immActtionnDoc, this.immActtionnDoc.name);
  }

  this.service
    .post('deviation2.php?type=saveConcernHodCOmmentMeha', formData)
    .subscribe((response) => {
      if (response['status'] === 'success') {
        alert('Concern HOD Comment Saved Successfully !!!!!!');
        this.getDeviationConcernApprovalData();
        data.resetForm();
        this.isView = false;
        this.selectedResult = [];
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    });
}


 



}
