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
  selectedResult = [];

  balances;
  selectedBalance = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingWeighingMaterials();
  }

  getPendingWeighingMaterials() {
    this.service.get('store/packing.php?type=getPendingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  getDeptBalances() {
    this.service.get('balance.php?type=getDeptBalances').subscribe(response => {
      this.balances = response;
    });
  }

  viewResult(index) {
    this.selectedResult = this.results[index];
    this.getDeptBalances();
    this.isView = true;
  }

  selectBalance(index) {
    index = index - 1;
    this.selectedBalance = this.balances[index];
  }

  saveWeighings(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    if (this.selectedBalance['status'] !== 'approve') {
      alertify.error('Balance Calibration pending');
      return;
    }

    let accepted_qty = 0;
    let containers = this.selectedResult['weight_containers'];
    for (let i = 0; i < containers.length; i++) {
      let container = containers[i];
      container['net_wt'] = +container['gross_wt'] - +container['tare_wt'];
      accepted_qty += +container['net_wt'];
      containers[i] = container;
    }

    let temp = {};
    temp['id'] = this.selectedResult['id'];
    temp['balance'] = this.selectedBalance['equipment_code'];
    temp['containers'] = containers;
    temp['accepted_qty'] = accepted_qty;
    this.service.post('store/packing.php?type=saveWeighingMaterials', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingWeighingMaterials();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }


}
