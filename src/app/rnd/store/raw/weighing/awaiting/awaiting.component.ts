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
  selectedResult = [];
  iscalibration=false;
  balances;
  selectedBalance = [];
  selectedReport=[];
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getPendingWeighingMaterials();
  }

  getPendingWeighingMaterials() {
    this.service.get('store/raw.php?type=getPendingWeighingMaterials').subscribe(response => {
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
   // this.selectedReport = this.results[index];
    this.getDeptBalances();
    this.isView = true;
  }

  selectBalance(index) {
    index = index - 1;
    this.selectedBalance = this.balances[index];
  }

  performcalibration(){
    this.iscalibration=true;
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

    let containers = this.selectedResult['weight_containers'];
    for (let i = 0; i < containers.length; i++) {
      let container = containers[i];
      container['net_wt'] = +container['gross_wt'] - +container['tare_wt'];
      containers[i] = container;
    }

    let damages = this.selectedResult['damage_containers'];
    for (let i = 0; i < damages.length; i++) {
      let container = damages[i];
      container['net_wt'] = +container['gross_wt'] - +container['tare_wt'];
      damages[i] = container;
    }

    let temp = {};
    temp['id'] = this.selectedResult['id'];
    temp['balance'] = this.selectedBalance['equipment_code'];
    temp['containers'] = containers;
    temp['damage_containers'] = damages;
    this.service.post('store/raw.php?type=saveWeighingMaterials', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingWeighingMaterials();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }
  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }
}
