import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { MEDICAP_PRODUCTION_FLOW, offerNextStep } from 'src/app/shared/medicap-production-flow';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  isView = false;
  results;
  selectedResult=[];

  from_date='';
  to_date='';
  today='';
  product_name='';
  showTailingBatches=false;
  constructor(private service:DataAccessService ,private datePipe:DatePipe, private router: Router) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPlans();
  }

  getPlans(){
    this.service.get('production/plan.php?type=getPendingPlans&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    if(this.selectedResult['bom_type']=='Blending Batch'){
      this.showTailingBatches=true;
    }else{
      this.showTailingBatches = false;
    }
    this.isView=true;}
  
  download(){
    // Use dedicated pending-approval PDF (same data as getPendingPlans list)
    this.service.open(
      'production/plan.php?type=downloadPendingBatchPlans&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }

  update(status, id){
    console.log(id);
    this.service.get('production/plan.php?type=approveBatchPlanStatus&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status']) {
        alertify.success('Status Updated Successfully');
        this.getPlans();
        this.isView = false;
        if (status === 'APPROVED' || status === 'Approved' || status === 'approve') {
          offerNextStep(
            MEDICAP_PRODUCTION_FLOW.productionBatchPlanning,
            'Production → Batch Planning (create work order)'
          );
        }
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
