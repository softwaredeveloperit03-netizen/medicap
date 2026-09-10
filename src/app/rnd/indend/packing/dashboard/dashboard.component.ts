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
  from_date='';
  to_date='';
  today='';
  results;
  items = [];
  material_subtype='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
  }

  ngOnInit(): void {
    this.getDeptIndendsLog();
  }

  getDeptIndendsLog() {
    this.service.get('purchase/indend/packing.php?type=getIndendsLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
      this.filterItem();
    });
  }
  download() {
    this.service.open('purchase/indend/packing.php?type=downloadIndendsLog&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  filterItem() {
    this.items = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['material_subtype'].toUpperCase().includes(this.material_subtype.toUpperCase())) {
        this.items[this.items.length] = material;
      }
    }
  }
  
  AllRecord(){
    this.items =this.results;
    this.material_subtype='';
    
}
}
