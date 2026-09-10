import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-actual',
  templateUrl: './actual.component.html',
  styleUrls: ['./actual.component.css']
})
export class ActualComponent implements OnInit {

  results;

  selectedResult = [];
  doller_value='';
  selectedBatch = [];
  product_for='';
  analytical_cost = 0;
  ccpc = 0;
  fright = 0;
  other_cost = 0;
  total = '0';
  actual_cost = '0';
  per_unit = '0';
  per_pack = '0';
  pack_cost=0;
  unit_cost=0;
  costing_type='Actual Costing';
  batch_cost=0
  materials;
  batch_size='';
  product_code='';

  isView = false;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getProducts();
  }

  getProducts(){
    this.service.get('planning/costing.php?type=getProducts').subscribe(response => {
      this.results = response;
    });
  }

  getProductDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.results[index];
    }
  }

  getMaterials(index) {
    index = index - 1;
    if (index !== -1) {
      let batches = this.selectedResult['batches'];
      this.selectedBatch = batches[index];
      this.calculate();
    }
  }


  actualCosting(form){
    if (!form.valid) {
          alertify.error('All fields are required!');
          return;
    }
    const temp=form.value;
    temp['product_for'] = this.product_for;
    temp['product_code'] = this.product_code;
    temp['batch_size'] = this.batch_size;
    temp['doller_value']=this.doller_value;
    temp['materials'] = JSON.stringify(this.selectedBatch);
    temp['analytical_cost']=this.analytical_cost;
    temp['ccpc_cost']=this.ccpc;
    temp['fright_cost']=this.fright;
    temp['other_cost']=this.other_cost;
    temp['batch_cost']=this.actual_cost;
    temp['unit_cost']=this.per_unit;
    temp['pack_cost']=this.per_pack;
    temp['costing_type'] = this.costing_type;
   

    this.service.post('planning/costing.php?type=saveCosting',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success('Record Save Successfully');
        form.resetForm();
        this.selectedBatch=[];
        this.router.navigate(['/management/costing']);
      }else(
        alertify.error('Error Occured')
      )
    });
   
  }
  calculate() {
    let total = 0;
    let actual_cost = 0;
    let batch_size = +this.selectedBatch['batch_size'];
    let materials = this.selectedBatch['materials'];
    for (let i = 0; i < materials.length; i++) {
      let material = materials[i];
      total += +material['amount'];
      actual_cost += +(material['qty']+material['amount']);
    }
    total += +this.analytical_cost;
    total += +this.ccpc;
    total += +this.fright;
    total += +this.other_cost;

    actual_cost += +this.analytical_cost;
    actual_cost += +this.ccpc;
    actual_cost += +this.fright;
    actual_cost += +this.other_cost;

    this.actual_cost = actual_cost.toFixed(2);
    this.per_unit = (+this.actual_cost / batch_size).toFixed(2);
    this.per_pack = (+this.per_unit * 10).toFixed(2);

    this.isView = true;
  }


}