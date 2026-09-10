import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  no=100;
  isView = false;
  results;
  weighing_critiera;
  selectedResult = [];
  iscalibration=false;
  balances;
  batches = [];
  selectedBalance = [];
  selectedReport=[];
  isProceed = false;
  selectedBatch = [];
  pack_size;
  rootnum;
  total_cont;
  final_container;
  numbers=[];
  numbers1=[];
  numbers2=[];
  balance;
  isTen=false;
  isHundred= false;
  isRoot= false;
  employess;
  weighings = [
    { id: 1, particular: 'From Label & Documents', check:''},
    { id: 2, particular: 'Weighing Bridge', check:''},
  ];
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getPendingWeighingMaterials();
    this.getEmpoloyees();
  }

  getPendingWeighingMaterials() {
    this.service.get('store/raw.php?type=getPendingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  getEmpoloyees(){
    this.service.get('common.php?type=getStoreEmployee').subscribe(response => {
      this.employess= response;
    });
  }

  getDeptBalances() {
    this.service.get('equipments.php?type=getStoreBalance').subscribe(response => {
      this.balances = response;
    });
  }

  viewResult(index) {
    this.selectedResult = this.results[index];
   // this.selectedReport = this.results[index];
    this.getDeptBalances();
    this.isView = true;
  }


  selectBatch(value){
    if (value == '10%') {
      this.isTen = true;
      this.isHundred = false;
      this.isRoot = false;

    } else if (value == '100% Verification') {

      this.isTen = false;
      this.isHundred = true;
      this.isRoot = false;

    }else if (value == '√n +1'){
      this.isTen = false;
      this.isHundred = false;
      this.isRoot = true;
    }
  }

  proceed(index) {
    this.batches=this.selectedResult["batches"]
    this.selectedBatch=this.batches[index];
    this.pack_size = this.selectedBatch['pack_size'];
    console.log('pack_size' , this.pack_size);
    let contain = this.selectedBatch['total_containers'];
    this.rootnum = Math.sqrt(contain);
    if(this.weighing_critiera=='√n +1'){
      this.final_container = parseFloat(this.rootnum + 1).toFixed(2);
     this.total_cont = Math.round(this.final_container);
     for (let i = 0; i < this.total_cont; i++) {
       let number= this.total_cont[i];
      let temp = {};
      temp['container_no'] ;
      temp['acgross_weight'] = 0;
      temp['lbnet_weight'] = 0;
      temp['actare_weight'] =  0;
      // temp['tolerance'] = this.no*this.selectedResult['tolerance']*1/number['lbnet_weight'];
      temp['acnet_weight'] = this.pack_size;
      this.numbers[i] = temp;




      console.log('numbers',this.numbers)
      }  

    }
    if(this.isHundred){
     this.total_cont = Math.round(contain);
     for (let i = 0; i < this.total_cont; i++) {
      let temp = {};
       temp['container_no'] ;
       temp['acgross_weight'] = 0;
       temp['actare_weight'] =  0;
      this.numbers1[i] = temp;
      console.log('numbers',this.numbers)
      }  


    }
    if(this.weighing_critiera=='10%'){
      this.total_cont = Math.ceil(contain*10/100);

      for (let i = 0; i < this.total_cont; i++) {
        let temp = {};
        //  temp['container_no'] ;
        // temp['gross_wt'] = 0;
        //  temp['tare_wt'] =  0;
        //  console.log('d', +temp['gross_wt'])
        // temp['net_wt'] = this.pack_size;
        this.numbers2[i] = temp;
        console.log('numbers',this.numbers)
        }  

     }
    
    
  
  

    this.isProceed = true;
    this.getPendingWeighingMaterials();
  }


  saveWeighingsList() {
  

    let temp =this.selectedResult;
    temp['id'] = this.selectedResult['id'];
    temp['batch_no'] = this.selectedBatch['batch_no'];
    temp['root_container'] = this.total_cont;
    temp['weight'] = this.numbers;
    this.service.post('store/raw.php?type=saveWeighingsList', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isProceed = false;
        this.getPendingWeighingMaterials();

      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
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
      container['diff_observed']=+container['net_weight']-+container['net_wt']
      containers[i] = container;
    }

    let damages = this.selectedResult['damage_containers'];
    for (let i = 0; i < damages.length; i++) {
      let container = damages[i];
      container['net_wt'] = +container['gross_wt'] - +container['tare_wt'];
      container['diff_observed']=+container['net_weight']-+container['net_wt']
      damages[i] = container;
    }

    let test = [];
    for (let i = 0; i < this.weighings.length; i++) {
      let weighing = this.weighings[i];
      if (weighing['check']) {
        test[test.length] = weighing['particular'];
      }
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
  
  updateWeighing(value, i) {
    this.weighings[i].check = value;
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }
  
}
