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
  isView=false;
  results;
  selectedResult=[]
  from_date='';
  to_date='';
  product_type='';
  units;
  company_unit='';
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getPlans();
    this.getUnits();
  }
 
  getPlans(){
    this.service.get('production/lot/plan.php?type=getPlans&company_unit='+this.company_unit+'&product_type='+this.product_type+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }
  getUnits() {
    this.service.get('common.php?type=getCompanyUnits').subscribe(response => {
      this.units = response;
    });
  } 


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  download(){
    this.service.open('production/lot/plan.php?type=downloadPlans&company_unit='+this.company_unit+'&product_date='+this.product_type+'&from_date='+this.from_date+'&to_date='+this.to_date);
  }
}


