import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessDamages();
  }

  getInprocessDamages() {
    this.service.get('store/raw.php?type=getInprocessDamages').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    let temp = this.selectedReport['damage_details'];
    temp['remark'] = this.remark;
    this.service.post('store/raw.php?type=updateDamageInspection&status=' + status + '&id=' + this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.remark = '';
        this.getInprocessDamages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
