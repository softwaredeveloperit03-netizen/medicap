import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];
  materials;
  balances;
  selectedBalance = [];
  isCountWeighing = false;
  isWeighing = false;
  isCount = false;
  countList= [];
  weighingList=[];

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

  selectResult(value){
    if (value == 'Counting') {
      this.isCount = true;
      this.isWeighing = false;
      this.isCountWeighing = false;

    } else if (value == 'Weighing') {
      //  this.getProducts();
      this.isCount = false;
      this.isWeighing = true;
      this.isCountWeighing = false;

    }else if (value == 'Counting Based on Weighing'){
      this.isCount = false;
      this.isWeighing = false;
      this.isCountWeighing = true;
    }
  }

  addCount(data){

    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.countList[this.countList.length] = temp;
    data.resetForm();
  }

  delCount(index) {
    this.countList.splice(index, 1);
  
  }
  addWeighing(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.weighingList[this.weighingList.length] = temp;
    data.resetForm();
  }

  delWeighing(index) {
    this.weighingList.splice(index, 1);
  
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
