import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {
  
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
  result:number=0
  // expected_weighing: any;
  // process: any;
  process:number=0;
  total_received:number=0;

  constructor(private service: DataAccessService) { }
//   calculateResult(){
//     this.result=this.process*this.expected_weighing/100
//  }

  ngOnInit(): void {
    this.getPendingWeighingMaterials();
  }

  getPendingWeighingMaterials() {
    this.service.get('store/packing.php?type=getRejectedWeighingMaterials').subscribe(response => {
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
test(){
  console.log(1);
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

  // saveWeighings(data) {
  //   if (!data.valid) {
  //     alertify.error('All fields are required');
  //     return;
  //   }

  //   if (this.selectedBalance['status'] !== 'approve') {
  //     alertify.error('Balance Calibration pending');
  //     return;
  //   }

  //   let accepted_qty = 0;
  //   let containers = this.selectedResult['weight_containers'];
  //   for (let i = 0; i < containers.length; i++) {
  //     let container = containers[i];
  //     container['net_wt'] = +container['gross_wt'] - +container['tare_wt'];
  //     accepted_qty += +container['net_wt'];
  //     containers[i] = container;
  //   }

  //   let temp = {};
  //   temp['id'] = this.selectedResult['id'];
  //   temp['balance'] = this.selectedBalance['equipment_code'];
  //   temp['containers'] = containers;
  //   temp['accepted_qty'] = accepted_qty;
  //   this.service.post('store/packing.php?type=saveWeighingMaterials', JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alertify.success(this.service.t('common.savedSuccess'));
  //       this.isView = false;
  //       this.getPendingWeighingMaterials();
  //     } else {
  //       alertify.error('Failed: An error occured, Please try again!');
  //     }
  //   });
  // }
  saveWeighings(data) {
      if (!data.valid) {
    alertify.error('All Fields Are Mandatory');
    return;
  }

  let temp = this.selectedResult;
  temp['id'] = this.selectedResult['id'];
  temp['weighingList'] = this.weighingList;


  this.service.post('store/packing.php?type=saveWeighingMaterials', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success(this.service.t('common.savedSuccess'));
      this.getPendingWeighingMaterials();
    } else {
      alertify.error('Failed: An error occured, Please try again!');
    }
  });
}

// let containers = this.selectedResult['weight_containers'];
// if (containers != null && containers != undefined) {
//   for (let i = 0; i < containers.length; i++) {
//     let container = containers[i];
//     container['net_wt'] = +container['gross_wt'] - +container['tare_wt'];
//     container['diff_observed'] = +container['net_weight'] - +container['net_wt']
//     containers[i] = container;
//   }
// }

}
