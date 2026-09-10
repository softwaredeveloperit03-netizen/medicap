import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from "@angular/common";
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]
})
export class NewComponent implements OnInit {

  isView = false;
  results;
  selectedReport = [];
  grn_date = '';
  remark = '';
  today='';
  constructor(private service: DataAccessService,private datePipe : DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getPendingGRN();
  }

  getPendingGRN() {
    this.service.get('store/packing.php?type=getPendingGRN').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  prepareGRN() {
    let temp = {};
    temp['id'] = this.selectedReport['id'];
    temp['remark'] = this.remark;
    temp['grn_date'] = this.grn_date;
    temp['materials'] = this.selectedReport['materials'];
    /* temp['short_qty'] = this.selectedReport['short_qty'];
    temp['accept_qty'] = this.selectedReport['accept_qty'];
    temp['reject_qty'] = this.selectedReport['reject_qty']; */
    this.service.post('store/packing.php?type=saveGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.remark = '';
        alertify.success('GRN Prepared successfully');
        this.isView = false;
        this.getPendingGRN();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
