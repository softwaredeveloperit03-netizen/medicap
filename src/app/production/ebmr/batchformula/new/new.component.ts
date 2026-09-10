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

  dosages;
  min=''
  products;
  max='';
  min_per='';
  selectedDosage = [];
  isTablet = true;
  selectedProduct = [];
  mfrs;
  isProd = false;
  selectedBatch = [];
  isBatch = false;

  batch_size = 0;
  lots = 0;
  max_per;
  stages;
  packing;
  materials = [];
  packings;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    
  }

  getProductbyType(product_type) {
    this.service.get('production/batchformula.php?type=getDosages&product_type='+ product_type).subscribe(response => {
      this.products = response;
    });
  }


  getProducts(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedDosage = this.dosages[index];
      this.products = this.selectedDosage['products'];

      if (this.selectedDosage['dosage_form'] == 'TABLET') {
        this.isTablet = true;
      } else {
        this.isTablet = false;
       // this.getStages('Direct Compression');
      }
    }
  }

  getProductDetails(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];
    this.mfrs = this.selectedProduct['mfrs'];
    this.isProd = true;
  }
  
  showBatch(index) {
    index = index - 1;
    let batches = this.selectedProduct['mfrs'];
    this.selectedBatch = batches[index];
    this.isBatch = true;
  }

  getMaterial(value){
    this.service.get('common.php?type=getMaterialsByType&material_type=' +value).subscribe(response => {
      this.packings = response;
    });
  }

  updateBatchQty() {
      let materials1 = this.selectedBatch['packing_materials'];
      for (let i = 0; i < materials1.length; i++) {
        let material = materials1[i];
        if (material['unit'] == "mg") {
          material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
          material['batch_unit'] = "Kg";
        } else if (material['unit'] == "Gram") {
          material["batch_qty"] = (material["qty"] * (this.batch_size / 1000)).toFixed(2);
          material['batch_unit'] = "Kg";
        } else {
          material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
          material['batch_unit'] = material['unit'];
        }
        materials1[i] = material;
      }


      let materials = this.selectedBatch['raw_materials'];
      for (let i = 0; i < materials.length; i++) {
        let material = materials[i];
        if (material['unit'] == "mg") {
          material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
          material['batch_unit'] = "Kg";
        } else if (material['unit'] == "Gram") {
          material["batch_qty"] = (material["qty"] * (this.batch_size / 1000)).toFixed(2);
          material['batch_unit'] = "Kg";
        } else {
          material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
          material['batch_unit'] = material['unit'];
        }
        materials[i] = material;
      }
  }

  saveBatch(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['raw_materials'] = this.selectedBatch['raw_materials'];
    temp['packing_materials'] = this.selectedBatch['packing_materials'];
    temp['stages'] = this.selectedBatch['stages'];
    temp['unit'] = this.selectedBatch['unit'];

    this.service.post('production/batchformula.php?type=saveBatchFormula', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alertify.success('Data Saved Successfully!');
        this.router.navigate(['/production/ebmr/batchformula']);
      } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }

}
