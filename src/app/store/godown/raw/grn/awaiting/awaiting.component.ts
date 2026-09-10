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
  results;
  selectedReport = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingGRN();
  }

  getPendingGRN() {
    this.service.get('store/raw.php?type=getPendingGRN').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  prepareGRN(data) {
    if (!data.valid) {
      alertify.error('');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedReport['id'];
    temp['short_qty'] = this.selectedReport['short_qty'];
    temp['vendor_no']=this.selectedReport['vendor_no'];
    temp['material_code']=this.selectedReport['material_code'];
    temp['unit']=this.selectedReport['unit'];
    temp['accept_qty'] = this.selectedReport['accept_qty'];
    temp['reject_qty'] = this.selectedReport['reject_qty'];
    temp['batches']=this.selectedReport['batches'];
    temp['inword_no'] = this.selectedReport['inword_no'];
    this.service.post('store/raw.php?type=saveGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Prepared successfully');
        this.isView = false;
        this.getPendingGRN();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + 'upload/challan/' + url;
    window.open(url, '_blank');
  }


}
