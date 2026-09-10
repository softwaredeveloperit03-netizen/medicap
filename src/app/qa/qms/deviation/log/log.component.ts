import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  constructor(
    private service: DataAccessService, 
    private router: Router
  ) {}

  ngOnInit(): void {
    this.getDeviationFOrClosure();
  }
  capaImple = '';

  result: any;
  isView = false;
  searchText = '';

  externalRevAndApproval = '';
  evaluHistoryOfDev = '';

  get filteredResults() {
    if (!this.result || !this.searchText || !this.searchText.trim()) {
      return this.result || [];
    }
    const q = this.searchText.trim().toLowerCase();
    return this.result.filter(
      (r: any) =>
        (r.deviation_no && String(r.deviation_no).toLowerCase().includes(q)) ||
        (r.devOccuredDate && String(r.devOccuredDate).toLowerCase().includes(q)) ||
        (r.devOccuredDept && String(r.devOccuredDept).toLowerCase().includes(q)) ||
        (r.DeviationType && String(r.DeviationType).toLowerCase().includes(q)) ||
        (r.identifiedBy && String(r.identifiedBy).toLowerCase().includes(q)) ||
        (r.status && String(r.status).toLowerCase().includes(q))
    );
  }

  getDeviationFOrClosure() {
    this.service
      .get(
        'deviation2.php?type=getDeviationLog&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.result = response;
      });
  } 

  download() {
    this.service.open(
      'deviation.php?type=DeviationLogMehaPdf&id=' + this.selectedResult['id']
    );
  }

  selectedResult: any = {};

  view(i: number) {
    const list = this.filteredResults;
    this.selectedResult = list[i];
    this.isView = true;
  }

  viewDevDoc(url: string) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
  }

  saveDeviation(data: any) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    const temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['deviation_no'] = this.selectedResult['deviation_no'];
    temp['devOccuredDept'] = this.selectedResult['devOccuredDept'];

    this.service
      .post('deviation2.php?type=saveDeviationClosure', JSON.stringify(temp))
      .subscribe((response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Saved Successfully !!!!!!');
          this.getDeviationFOrClosure();
          data.resetForm();
          this.isView = false;
          this.selectedResult = {};
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      }, (error: any) => {
        alertify.error('Error saving deviation');
      });
  }

}

