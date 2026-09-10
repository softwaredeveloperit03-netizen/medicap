import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-closing1',
  templateUrl: './closing1.component.html',
  styleUrls: ['./closing1.component.css']
})
export class Closing1Component implements OnInit {

  additional_evaluation = '';
  results;
  isView = false;
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }

  getInitiatedCC() {
    this.service.get('qms/ccpermanant.php?type=getPendingClosingApproval').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/ccpermanant/' + link);
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    temp['cc_no'] = this.selectedResult['cc_no'];
    this.service.post('qms/ccpermanant.php?type=closeCCApproval' + '&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
      } else {
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }
}
