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
  results1
  constructor(private service: DataAccessService,private datePipe : DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getPendingGRN();
    this.getPendingChallans();
  }

  getPendingGRN() {
    this.service.get('store/packing.php?type=getPendingGRN').subscribe(response => {
      this.results = response;
    });
  }
  getPendingChallans(){
    this.service.get('store/challan.php?type=getPendingChallans').subscribe(response => {
      this.results1 = response;
    });
  }
  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  prepareGRN(data) {

    if(!data.valid){
      alertify.error('Select Date!!!!!!!!!!!!!');
      return;
    }


    let temp = data.value;
    temp['id'] = this.selectedReport['id'];
    temp['remark'] = this.remark;
    temp['grn_date'] = this.grn_date;
    temp['materials'] = this.selectedReport['materials'];
    temp['accept_qty'] = this.selectedReport['received_qty'];

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
