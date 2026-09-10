import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReceiving();
  }

  getData(value){
   
      this.getPendingReceiving();
   
  }
  getPendingReceiving(){
    this.service.get('production/technical.php?type=getPendingReceivingFg').subscribe(response => {
      this.results = response;
    });
  }
  getPendingReceiving_PK(){
    this.service.get('production/technical.php?type=getPendingReceiving_PK').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  sampleQty = 0;
  unit = '';

  RequiredQty;
  Requiredunit;
  Stability_Qty;
  Stability_unit;
  control_sample_Qty;
  control_sample_unit;
  Marketing_sample_Qty;
  Marketing_sample_unit;
  receive(){

    let temp = {};
    temp['sampleQty'] = this.sampleQty
    temp['unit'] = this.unit
temp['RequiredQty']=this.RequiredQty
temp['Requiredunit']=this.Requiredunit
temp['Stability_Qty']=this.Stability_Qty
temp['Stability_unit']=this.Stability_unit
temp['control_sample_Qty']=this.control_sample_Qty
temp['control_sample_unit']=this.control_sample_unit
temp['Marketing_sample_Qty']=this.Marketing_sample_Qty
temp['Marketing_sample_unit']=this.Marketing_sample_unit

    this.service.post('production/technical.php?type=completeFGonlySampling&id=' + this.selectedResult['id'] +'&s_id=' + this.selectedResult['s_id']+
      '&ti_no=' + this.selectedResult['ti_no'],JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.sampleQty = 0;
        alert('Sample Collected Successfully!!!!!');
        this.getPendingReceiving();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });


  }
  receivePK(){

    let temp = {};
    temp['sampleQty'] = this.sampleQty
    temp['unit'] = this.unit

    this.service.post('production/technical.php?type=completeFGSampling_pk&id=' + this.selectedResult['id'] +
      '&ti_no=' + this.selectedResult['ti_no'],JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.sampleQty = 0;
        alert('Sample Collected Successfully!!!!!');
        this.getPendingReceiving();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });


  }










}
