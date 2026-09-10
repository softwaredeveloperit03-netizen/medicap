import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bridge',
  templateUrl: './bridge.component.html',
  styleUrls: ['./bridge.component.css']
})
export class BridgeComponent implements OnInit {
  total_weight=0;
  net_wt=0;
  container_tarewt=0;
  totaltare_wt=0;
  v_grosswt=0;
  v_tarewt=0;
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
  bridges=[];
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
    this.getPendingWeighingBridge();
  }

  getPendingWeighingBridge() {
    this.service.get('store/raw.php?type=getPendingWeighingBridge').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  addWeighBridge(data){
    this.bridges[this.bridges.length]=data.value;
    data.reset();
  }
  del(index){
    this.bridges.splice(index,1);
  }



  
  saveWeighings() {
    if (this.bridges.length == 0) {
      alertify.error('Add Weigh');
      return;
    }
    this.service.post('store/raw.php?type=saveWeighingBridge', JSON.stringify(this.bridges)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingWeighingBridge();
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

  calculation(){
    this.totaltare_wt=this.container_tarewt*this.total_weight;
    this.net_wt=(this.v_grosswt-this.v_tarewt)-this.totaltare_wt;
  }

}
