import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plan',
  templateUrl: './plan.component.html',
  styleUrls: ['./plan.component.css']
})
export class PlanComponent implements OnInit {
  results;
  isView=false;
  dosages;
  product;
  batch;
  status='';
  product_code='';
  dosage_form='';
  batch_size ='';
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getApprovedBatchPlans();
    this.getDosages();
  }
  getApprovedBatchPlans(){
    this.service.get('production/plan.php?type=getApprovedBatchPlans&dosage_form=' +this.dosage_form +'&product_code=' +this.product_code +'&batch_size=' +this.batch_size  +'&status='+this.status).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult= this.results[index];
    this.isView =true;
  }
  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosages=response;
    });
  }
  getProducts(dosage_form){
    this.service.get('common.php?type=getProductsByDosage&dosage_form='+dosage_form).subscribe(response=>{
      this.product=response;
    });
  }
  getBatches(product_code){
    this.service.get('common.php?type=getBatchSizesByProduct&product_code='+product_code).subscribe(response=>{
      this.batch=response;
    });
  }
}
