import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  product;
  product_code='';
  product_for='';
  costing_type='';
  // material=[];
  loading;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getCostingReport();
    this.getProducts();
  }
  getCostingReport(){
    this.service.get('planning/costing.php?type=getCostingReport&product_code='+this.product_code+'&costing_type='+this.costing_type+'&product_for='+this.product_for).subscribe(response=>{
      this.results=response;
    });
  }
  getProducts(){
    this.service.get('planning/costing.php?type=getProducts').subscribe(response=>{
      this.product=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
