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
  batches = [];
  isShow=false;
  selectedProduct = [];
  selectedResult = [];
  selectedStage=[];
  units;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getUnits();
  }

  getUnits() {
    this.service.get('common.php?type=getCompanyUnits').subscribe(response => {
      this.units = response;
    });
  } 
  getProducts(product_type) {
    this.service.get('production/bmr/plan.php?type=getProducts&product_type='+product_type).subscribe(response => {
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
    /*   console.log('fdfd',this.selectedStage); */
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
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_no']=this.selectedResult['batch_no'];
    temp['product_type']=this.selectedResult['product_type'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['raw_materials']=this.selectedResult['raw_materials'];
    temp['packing_materials']=this.selectedResult['packing_materials'];
    temp['stages']=this.selectedResult['stages']; 

    this.service.post('production/bmr/plan.php?type=savePlan', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alertify.success(this.service.t('common.savedSuccess'));
        this.router.navigate(['/production/bmr/plan']);
      } else {
        alertify.error('An error occured, Please try again');
      }
    });
  }

}
