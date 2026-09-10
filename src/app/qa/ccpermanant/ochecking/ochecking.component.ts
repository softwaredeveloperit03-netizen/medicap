import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ochecking',
  templateUrl: './ochecking.component.html',
  styleUrls: ['./ochecking.component.css']
})
export class OcheckingComponent implements OnInit {

  results;
  isView = false;
  selectedResult = [];
  remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
  }
  getInitiatedCC() {
    this.service.get('qms/ccpermanant.php?type=getInitiatedCC').subscribe(response => {
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

  update(data, status) {

    if (!data.valid) {
      alertify.error('All feilds are required');
      return;
    }

    let temp = {};
    temp['remark'] = this.remark;
    temp['cc_no'] = this.selectedResult['cc_no'];
    this.service.post('qms/ccpermanant.php?type=checkInitiatedCC&status=' + status + '&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
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
