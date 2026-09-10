import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;

  results;
  selectedReport = [];

  remark = '';

  constructor(private service: DataAccessService) { }

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
    temp['short_qty'] = this.selectedReport['short_qty'];
    temp['accept_qty'] = this.selectedReport['accept_qty'];
    temp['reject_qty'] = this.selectedReport['reject_qty'];
    temp['vendor_no']=this.selectedReport['vendor_no'];
    temp['material_code']=this.selectedReport['material_code'];
    temp['unit']=this.selectedReport['unit'];
    temp['batches']=this.selectedReport['batches'];
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
