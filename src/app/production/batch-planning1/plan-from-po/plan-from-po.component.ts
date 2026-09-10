import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plan-from-po',
  templateUrl: './plan-from-po.component.html',
  styleUrls: ['./plan-from-po.component.css'],
  providers:[DatePipe]
})
export class PlanFromPoComponent implements OnInit {

  isView = false;
  results;
  selectresult=[];

  from_date='';
  to_date='';
  today='';
  product_name='';
  units;
  clients;
  client_wise_table =true;
  product_wise_table = false;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPlans();
    this.getUnits();
    this.getClients();
  }

  checkType(val){
    if(val=='Product Wise'){
      this.product_wise_table = true;
      this.client_wise_table = false;
    }else if(val=='Client Wise'){
      this.client_wise_table = true;
      this.product_wise_table = false;

    }
  }

  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response =>{
      this.units =response;
    });
  }
  
  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients=response;
    })
  }


  getPlans(){
    // this.service.get('production/plan.php?type=getPlans&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
    this.service.get('marketing/po.php?type=getSalesOrderLog').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectresult=this.results[index];
    console.log(this.selectresult);
    this.isView=true;
  }
  
  download(){
    this.service.open('production/bmr/plan.php?type=downloadPlans&from_date='+this.from_date+'&to_date='+this.to_date);
  }

}

