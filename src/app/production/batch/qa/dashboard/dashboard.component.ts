import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MEDICAP_PRODUCTION_FLOW, offerNextStep } from 'src/app/shared/medicap-production-flow';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  isView=false;
  dosages;
  product;
  batch;
  status='';
  product_code='';
  dosage_form='';
  product_name;
  from_date;
  to_date;
  batch_size ='';
  selectedResult=[];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getApprovedBatchPlans();
   // this.getDosages();
  }
  getApprovedBatchPlans(){
    this.service.get('production/workorder.php?type=get_qa_approved_work_orders&material_type=RM').subscribe(response=>{
      this.results=response;
    });
  }
  getPlans(){
    this.service.get('production/workorder.php?type=get_qa_approved_work_orders_from_to&material_type=RM&product_name='+this.product_name+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult= this.results[index];
    this.isView =true;
    console.log(this.selectedResult);
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
  
 
  // sendDispenseRequest(data){ 
  //   // if (this.yeild_per == 0) {
  //   //   alertify.error(' Add Yield ');
  //   //   return;
  //   // }
  
  //   // let temp=data.value;
  //   // temp['yeild_per']=this.yeild_per;
  //   // temp['saveyeild_list']=this.saveyeild_list;
  //   // // this.selectedResult= this.results[id];
    
  //     this.service.get('production/workorder.php?type=update_dispense_request&material_type=RM&id='+this.selectedResult['id']).subscribe(response => {
  //       if (response['status'] == "success") {
  //         alertify.success('Work Order has been send to Approval');                  
  //        this.getApprovedBatchPlans()
  //       } else {
  //         alertify.error('Failed: '+response['status']);
  //       }
  //     });
    
    
  // }
  sendDispenseRequest(id){ 
    this.selectedResult= this.results[id];
      this.service.get('production/workorder.php?type=update_dispense_request&material_type=RM&id='+this.selectedResult['id']).subscribe(response => {
        if (response['status'] == "success") {
          alertify.success('Work Order has been send to Approval');                  
         this.getApprovedBatchPlans();
         offerNextStep(
           MEDICAP_PRODUCTION_FLOW.storeDispensingRawRequest,
           'Store → Raw Dispensing (Awaiting Requests)'
         );
        } else {
          alertify.error('Failed: '+response['status']);
        }
      });
    
    
  }
  container_no;
  gross_wt;
  tare_wt;
  net_wt;
  saveyeild_list=[];
  total_weight =0;
  yeild_per1;
  yeild_per=0;
  stage_input_qty=0;

    saveyeild(data){
      if (!data.valid) {
        alert('All fields are required');
        return;
      }
   
      if (this.stage_input_qty ==0) {
        alertify.error(' Add stage_input_qty ');
        return;
      }
      let temp = data.value;
   
      temp["container_no"] = this.container_no;
      temp["gross_wt"] = this.gross_wt;
      temp["tare_wt"] = this.tare_wt;
      temp["net_wt"] = this.net_wt;
  
      this.total_weight = this.total_weight + parseInt(this.net_wt);
      this.yeild_per1= this.stage_input_qty * this.total_weight/100;
 
  
      this.saveyeild_list[this.saveyeild_list.length ]= temp;
    
      data.reset();
      this.yeild_per=this.yeild_per1;
      console.log(this.yeild_per1);
    
    }
}
