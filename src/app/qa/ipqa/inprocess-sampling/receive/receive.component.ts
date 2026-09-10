import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-receive',
  templateUrl: './receive.component.html',
  styleUrls: ['./receive.component.css']
})
export class ReceiveComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    
  }

  getData(value){
    if(value=='Production'){
      this.getPendingReceiving();
    }
    else if(value=='Packing'){
      this.getPendingReceiving_PK();
    }
  }
  getPendingReceiving(){
    this.service.get('production/technical.php?type=getPendingReceiving').subscribe(response => {
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

 
  receive(){

    let temp = {};
    temp['sampleQty'] = this.sampleQty
    temp['unit'] = this.unit

    this.service.post('production/technical.php?type=completeFGSampling&id=' + this.selectedResult['id'] +
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
