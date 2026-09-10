import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-riskassessment',
  templateUrl: './riskassessment.component.html',
  styleUrls: ['./riskassessment.component.css'],
})
export class RiskassessmentComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getDeviationForAssessmentByQA();
  }

  result;
  isView = false;
  isCapa = false;
  searchText = '';

  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

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

  getDeviationForAssessmentByQA() {
    this.service
      .get(
        'deviation2.php?type=getDeviationForAssessmentByQA&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.result = response;
      });
  }
  capaImplemented: string = '';

  checkNewDocument() {
    console.log('Selected value:', this.capaImplemented);

    if (this.capaImplemented === 'Yes') {
      this.isView = false;
      this.isCapa = true;
    } else {
      // Handle other cases
      console.log('No/NA selected - maybe hide something');
    }
  }


  selectedResult = [];
  showExtraField = false;

  view(i) {
    const list = this.filteredResults;
    this.selectedResult = list[i];
    this.isView = true;

    const tcdDate = new Date(this.selectedResult['tcd']);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    this.showExtraField = tcdDate <= today;
  }

  viewDevDoc(url) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
  }
  onCloseCheck() {
    this.isView = false;
    this.isCapa = false;
  }
  onClose() {
    this.isView = true;
    this.isCapa = false;
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
        'deviation2.php?type=saveDeviationAsessmentByQaMeha',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getDeviationForAssessmentByQA();
          data.resetForm();
          this.isView = false;
          this.selectedResult = [];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    // uploadData.append('plan',JSON.stringify(this.capas));
    this.service
      .post('/qms/capa2.php?type=saveMehaCAPA', uploadData)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          data.resetForm();
            this.isCapa = false;
          alertify.success('Successfully Saved');
        } else {
          alertify.error('An error has occurred, please try again');
        }
      });
  }
}
