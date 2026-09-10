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

  products;
  res=0;
  planned_qty=0;
  batch_size=0;
  lowest_batch=0;
  
  batches = [];
  isShow=false;
  selectedProduct = [];
  selectedResult = [];
  selectedStage=[];
  bom_type='';
  bom_for='';
  total_qty = 0;
  batches_no = 0;
  final_qty = 0;
  remaining_qty = 0;
  remaining_batches;
  tailing_plan_qty = 0;
  tailing_batches_no = 0;
  tailing_final_plan_qty = 0;
  fresh_final_qty = 0;
  noof_batches=0;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {}

  // calculate() {
  //   if (+this.selectedProduct['tailing_qty'] > 0) {
  //     if (+this.selectedProduct['tailing_qty'] >= this.tailing_plan_qty) {
  //       this.tailing_batches_no = Math.floor(this.tailing_plan_qty / +this.selectedResult['dispatch_qty']);
  //     } else {
  //       this.tailing_plan_qty = +this.selectedProduct['tailing_qty'];
  //     }
  //   } else {
  //     this.tailing_plan_qty = 0;
  //     this.tailing_batches_no = 0;
  //   }
    
  //   this.remaining_qty = this.total_qty - this.tailing_plan_qty;

  //   this.tailing_final_plan_qty = this.tailing_batches_no * +this.selectedResult['dispatch_qty'];

  //   this.remaining_batches = Math.round(this.remaining_qty / +this.selectedResult['min_output_qty']);

  //   this.fresh_final_qty = +this.selectedResult['min_output_qty'] * this.remaining_batches;

  //   this.final_qty = this.tailing_final_plan_qty + this.fresh_final_qty;

  //   this.batches_no = this.remaining_batches + this.tailing_batches_no;

  //   let materials = this.selectedResult['raw_materials'];
  //   for (let i = 0; i < materials.length; i++) {
  //     let material = materials[i];
  //     material['plan_qty'] = +parseFloat((+material['qty'] * this.total_qty) + '').toFixed(2);
  //     materials[i] = material;
  //   }
  //   this.selectedResult['raw_materials'] = materials;
  // }


  calculate2(){
    let batchArray=[];
    this.res=this.total_qty*1/this.batch_size*1;
    this.noof_batches=Math.round(this.res);
    let materials = this.selectedResult['raw_materials'];
    for (let i = 0; i < materials.length; i++) {
      let material = materials[i];
      this.planned_qty=material['received_qty'];
      // this.lowest_batch= Math.round(this.planned_qty/this.batch_size)
      material['req_qty'] = +parseFloat((+material['qty'] *  this.noof_batches) + '').toFixed(2);
      material['shortage_qty'] = +parseFloat((+material['req_qty'] -  +material['received_qty']) + '').toFixed(2);
      material['batch_planned'] =  Math.round(+parseFloat((+material['received_qty'] /  +material['qty']) + ''));
      batchArray.push(material['batch_planned']);
      if(material['shortage_qty']<0)
      {
        material['shortage_qty']=0;
      }
      materials[i] = material;
    }
    this.lowest_batch = Math.min(...batchArray);
    this.selectedResult['raw_materials'] = materials;
  }
  
  qtyPlanned(){
    let data = this.lowest_batch * this.batch_size;
    this.planned_qty = data;
  }







  getProducts(product_type) {
    this.service.get('production/plan.php?type=getProducts&product_type='+product_type+'&bom_type='+this.bom_type+'&bom_for='+this.bom_for).subscribe(response => {
      this.products = response;
    });
  }

  getProductDetails(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];
      this.batches = this.selectedProduct['batches'];
  }

  getBatchDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.batches[index];
      this.selectedStage = this.selectedResult['stages'];
      this.calculate2();
      this.isShow=true;
    } else {
      this.batches = [];
    }

  }
  saveBatchPlan(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value;
    temp['product_code'] = this.selectedResult['product_code'];
    temp['bom_no'] = this.selectedResult['mfr_no'];
    temp['min_output_qty'] = this.selectedResult['min_output_qty'];
    temp['max_output_qty'] = this.selectedResult['max_output_qty'];
    temp['dispatch_qty'] = this.selectedResult['dispatch_qty'];
    temp['unit'] = this.selectedResult['unit'];
    temp['total_batches'] = this.noof_batches;
    temp['fresh_batches'] = this.remaining_batches;
    temp['raw_materials'] = this.selectedResult['raw_materials'];
    temp['packing_materials'] = this.selectedResult['packing_materials'];

    this.service.post('production/plan.php?type=savePlan', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alertify.success(this.service.t('common.savedSuccess'));
        this.router.navigate(['/planning/production']);
      } else {
        alertify.error('An error occured, Please try again');
      }
    });
  }

}
