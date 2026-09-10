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

  selectBalance(index) {
    index = index - 1;
    this.selectedBalance = this.balances[index];
  }

  performcalibration(){
    this.iscalibration=true;
  }

 
  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }
}
