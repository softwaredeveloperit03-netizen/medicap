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
  dosages;
  isView=false;
  results;
  selectedResult=[]
  from_date='';
  to_date='';
  product_type='';
  constructor(private service: DataAccessService ,private datePipe: DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getPlans();
  /*   this.getDosageForms(); */
  }
  
  getDosageForms() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getPlans(){
    this.service.get('planning/plan.php?type=getPendingBatchPlan&product_type='+this.product_type+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  download(){
    this.service.open('planning/plan.php?type=downloadPlans&product_type='+this.product_type+'&from_date='+this.from_date+'&to_date='+this.to_date);
  }

}
