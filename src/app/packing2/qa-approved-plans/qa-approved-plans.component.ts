import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-qa-approved-plans',
  templateUrl: './qa-approved-plans.component.html',
  styleUrls: ['./qa-approved-plans.component.css']
})
export class QaApprovedPlansComponent implements OnInit {

  is_view_work_order;
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
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getApprovedBatchPlans();
   // this.getDosages();
  }
  getApprovedBatchPlans(){
    this.service.get('production/workorder.php?type=get_qa_approved_work_orders&material_type=PM').subscribe(response=>{
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

  download() {
    this.service.open('production/plan.php?type=downloadBatchPlans&dosage_form=' +this.dosage_form +'&product_code=' +this.product_code +'&batch_size=' +this.batch_size  +'&status='+this.status);
  }
  
 
  sendDispenseRequest(id){ 
    this.selectedResult= this.results[id];
      this.service.get('production/workorder.php?type=update_dispense_request&id='+this.selectedResult['id']).subscribe(response => {
        if (response['status'] == "success") {
          alertify.success('Work Order has been send to Approval');                  
         this.getApprovedBatchPlans()
        } else {
          alertify.error('Failed: '+response['status']);
        }
      });
    
    
  }

}
