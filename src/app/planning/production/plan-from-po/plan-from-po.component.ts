 import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-plan-from-po',
  templateUrl: './plan-from-po.component.html',
  styleUrls: ['./plan-from-po.component.css'],
   
})

export class PlanFromPoComponent implements OnInit {

  isView = false;
  results;
  selectresult=[];

 
  product_name='';
 
 
  client_wise_table =true;
  product_wise_table = false;
  constructor(private service:DataAccessService ,private router: Router ) {     
 
  }

  ngOnInit() {
    this.getPlans();
  
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
 
 

  getPlans(){
     this.service.get('marketing/po.php?type=getMarketingPoPlan').subscribe(response=>{
      this.results=response;
    });
  }


  transfer(data){
    this.router.navigate(['/planning/production/bulk'], { state: { plan_data:data } });

  }

 
 
 

}

