import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  batches = [];
  available=[];
  selectedBatch = [];
  selectedAvailableBatch=[];
  materials = [];
  avlmaterial=[];
  batchData=[];
  batch;
  mfg_date;
  exp_date;
  qty;
  unit;
  batchAdd=[{batchno:this.batchData['batch_no'],mfg_date:this.batchData['mfg_date'],exp_date:this.batchData['exp_date'],
    qty:this.batchData['qty']
  }];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingPlans();
  }

  getPendingPlans() {
    this.service.get('planning/workorder.php?type=getPendingPlans').subscribe(response=> {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.batches = this.selectedResult['batches'];
    this.batchData=this.selectedResult['avl_batches'];
    console.log( this.batchData);

    for(let i=0;i<this.batchData.length;i++){
     
    }


    let batch=this.batchData[0];
    this.batch=this.batchData['batch_no'];
    this.mfg_date=this.batchData['mfg_date'];
    this.exp_date=this.batchData['exp_date'];
    this.qty=this.batchData['qty'];
    this.unit=this.batchData['unit'];
    this.batchAdd=this.batch,this.mfg_date,this.exp_date,this.qty,this.unit;
    console.log('batchdata',this.batch);
    this.isView=true;
  }
  
  getMaterials(index) {
    this.selectedBatch = this.batches[index];
    this.materials = this.selectedBatch['materials'];
  }


  saveRequirement(data){
    let temp=data.value;
    
  //   temp['batches']=[{batchno:this.batchData['batch_no'],mfg_date:this.batchData['mfg_date'],exp_date:this.batchData['exp_date'],
  //   qty:this.batchData['qty']
  // }];

    temp['work_order_no']=this.selectedResult['workorder_no'];
    temp['po_no']=this.selectedResult['po_no'];
    temp['po_date']=this.selectedResult['po_date'];
    temp['po_type']=this.selectedResult['po_type'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['estimate_qty']=this.selectedResult['estimate_qty'];
    temp['unit']=this.selectedResult['unit'];
    temp['available_qty']=this.selectedResult['avl_qty'];
    temp['short_qty']=this.selectedResult['short_qty'];
    temp['batches']=this.selectedResult['avl_batches'];
   temp['suggested']=this.selectedResult['batches'];
   temp['materials']=this.materials;
   console.log('no',this.selectedResult['workorder_no']);
    this.service.post('planning/consoladated.php?type=saveRequirement',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.isView=false;
        this.getPendingPlans();
      }else{
        alertify.error('some error Ocuured!');
      }
    });
  }

}
