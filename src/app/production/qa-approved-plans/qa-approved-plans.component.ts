import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-qa-approved-plans',
  templateUrl: './qa-approved-plans.component.html',
  styleUrls: ['./qa-approved-plans.component.css'],
  providers:[DatePipe]
})
export class QaApprovedPlansComponent implements OnInit {

  isView = false;
  results;
  selectedResult=[];
  production_employees;
  from_date='';
  to_date='';
  today='';
  product_name='';  
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPlans();
    this.getProductionPersons();
  }

  getPlans(){
    // this.service.get('production/plan.php?type=getPlans&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
    this.service.get('production/plan.php?type=getApprovedPlansForProduction&show_only_approved=true&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  proceed(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  
  download(){
    this.service.open('production/bmr/plan.php?type=downloadPlans&from_date='+this.from_date+'&to_date='+this.to_date);
  }
  getProductionPersons() {
    this.service.get('common.php?type=getProductionPersons').subscribe(response => {
      this.production_employees = response;
    });
  }
  saveApprovedBatches(form){
    // console.log(this.selectedResult['batches1']);
    if(this.selectedResult['batches1'].length==0){
      alertify.error('No batches to approve');
           return;
    }
     let data_list=[];
     let has_selected =false;
     for (let item of this.selectedResult['batches1']) {
       if (item.checked === true) {
         has_selected = true;
         if(item.bmr_received_by == null || item.bmr_received_by == undefined ||item.bmr_received_by == "")
         {
           alertify.error('Please select BMR received by');
           return;
         }
         if(item.bmr_received_on == null || item.bmr_received_on == undefined ||item.bmr_received_on == "0000-00-00")
         {
           alertify.error('Please select issue date');
           return;
         }
         
      
       }
     }
 
     if(!has_selected){
       alertify.error('Please select batches to proceed');
       return;
     }
 
 
     for (let item of this.selectedResult['batches1']) {
       if (item.checked === true) {
         has_selected = true;
           let obj =  {
               "bmr_received_by": item.bmr_received_by,
               "bmr_received_on": item.bmr_received_on,
               "batch_id": item.id
 
           };
           this.service.post('dispatch/opening.php?type=updateBatchforProduction',JSON.stringify(obj)).subscribe(response=>{
             if(response['status']=='success'){
             //  alertify.success('Sent To QA Successfully');
             }else{
               alertify.error('Failed: Please Try Again');
             }
           }); 
       }
      
   }
   if(has_selected){
    this.isView=false;
    this.getPlans();
   }
}
}
