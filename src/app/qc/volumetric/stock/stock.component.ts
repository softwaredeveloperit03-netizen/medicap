import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-stock',
  templateUrl: './stock.component.html',
  styleUrls: ['./stock.component.css']
})
export class StockComponent implements OnInit {

  isView = false;
  results;
   constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVolumetricPreparation();
  
  }
  selectedResult =[];

  
 

  employee;

  getVolumetricPreparation() {
    this.service.get('qc/volumetric.php?type=getStockVolumetic').subscribe(response => {
      this.results = response;
    });
  }
  
   
  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  
selectedDesp= [];
isdespDetails = false;

  viewDesp(index){
    this.selectedDesp = this.selectedResult['batch_data'][index];
    this.isdespDetails = true;
  }

  







}





