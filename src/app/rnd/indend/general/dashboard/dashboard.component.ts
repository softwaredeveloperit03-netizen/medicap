import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  results;
  to_date='';
  from_date='';
  today='';
  item = [];
  material_type='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
  }
  ngOnInit() {
    this.getDeptIndendsLog();
  }

  getDeptIndendsLog() {
    this.service.get('purchase/indend/general.php?type=getIndendsLog').subscribe(response => {
      this.results = response;
      this.filterStock();
    });
  }
  download() {
    this.service.open('purchase/indend/general.php?type=downloadIndendsLog')
  }

  filterStock() {
    this.item = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['material_type'].toUpperCase().includes(this.material_type.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  AllRecord(){
    this.item =this.results;
    this.material_type='';

    
  }
}
