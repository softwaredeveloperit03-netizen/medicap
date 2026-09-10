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

  ngOnInit() {
    this.getPendingGRN();
  }

  getPendingGRN() {
    this.service.get('qc/standard/receiving.php?type=getPendingGRN').subscribe(response => {
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
    this.service.post('qc/standard/receiving.php?type=saveGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Prepared successfully');
        this.isView = false;
        this.getPendingGRN();
        this.remark='';
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
