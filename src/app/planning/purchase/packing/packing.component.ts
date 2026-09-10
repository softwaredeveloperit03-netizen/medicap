  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-packing',
  templateUrl: './packing.component.html',
  styleUrls: ['./packing.component.css'],
  providers:[DatePipe]
})
export class PackingComponent implements OnInit {

  
  from_date='';
  to_date='';
  results;
  trade_name;
  selectedResult=[];
  isView=false;
  loading;
  
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit(): void {
    this.viewOrder();
   
  }
  viewOrder(){
    this.service.get('headquarter.php?type=getPackingPurchaseOrders&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  download(){
    this.service.open('headquarter.php?type=downloadPackingPurchaseOrders&from_date='+this.from_date+'&to_date='+this.to_date)
  }

}
