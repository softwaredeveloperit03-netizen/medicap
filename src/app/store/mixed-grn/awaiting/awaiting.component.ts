import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  total_qty;
  results;
  selectedReport = [];

  remark = '';
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingMixedGRN();
  }

  getPendingMixedGRN() {
    this.service.get('store/raw.php?type=getPendingMixedGRN').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
    this.total_qty =this.selectedReport['previous_stock'] + +this.selectedReport['received_qty'];
  }

  prepareMixedGRN() {
    let temp = {};
    temp =this.selectedReport;
    temp['id'] = this.selectedReport['id'];
    temp['total_qty']=this.total_qty;
    temp['qty']=this.selectedReport['qty'];
    temp['ar_no']=this.selectedReport['ar_no'];
    this.service.post('store/raw.php?type=saveMixedGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('MixedGRN Prepared successfully');
        this.isView = false;
        this.getPendingMixedGRN();
        this.remark='';
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
