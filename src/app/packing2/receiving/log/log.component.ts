import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  results;
  from_date='';
  to_date='';
  product_name='';
  results1;
  constructor(private service:DataAccessService,private datePipe:DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01'); 
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
   }

  ngOnInit() {
    this.getLogs();
  }
  getLogs(){
    this.service.get('packing/semifinish.php?type=getReceivingLog&form_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results1=response;
      this.filterProducts();
    })
  }
  download(){
    this.service.open('packing/semifinish.php?type=downloadReceivingLog&product_name='+this.product_name+'&form_date='+this.from_date+'&to_date='+this.to_date)
  }
  filterProducts() {
    this.results = [];
    for (let i = 0; i < this.results1.length; i++) {
      let prod = this.results1[i];
      if (prod['product_name'].toUpperCase().includes(this.product_name.toUpperCase())) {
        this.results[this.results.length] = prod;
      }
    }
  }

}
