import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  dosages;
  products;

  selectedDosage = [];
  isTablet = true;
  selectedProduct = [];
  mfrs;
  isProd = false;
  selectedBatch = [];
  isBatch = false;

  batch_size = 0;
  lots = 0;

  stages;
  packing;

  instructions = [];

  flow: File;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    /* this.getDosages(); */
    this.getProducts();
  }

  onFileChanged1(event) {
    if (event.target.files.length !== 0) {
      this.flow = event.target.files[0];
    }
  }

  /* getDosages() {
    this.service.get('production/product.php?type=getDosagesforMaster').subscribe(response => {
      this.dosages = response;
    });
  } */

  getProducts(){
    this.service.get('production/master.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  getStages(value) {
    if (this.selectedDosage['dosage_form'] !== 'TABLET') {
      value = 'Direct Compression';
    }
    this.service.get('production/bmr.php?type=getStages&granulation_type=' + value + '&dosage_form=' + this.selectedDosage['dosage_form']).subscribe(response => {
      this.stages = response;
    });
  }

  getPacking(value) {
    this.service.get('production/bmr.php?type=getStages&packing=' + value).subscribe(response => {
      this.packing = response;
    });
  }

 /* getProducts(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedDosage = this.dosages[index];
      this.products = this.selectedDosage['products'];

      if (this.selectedDosage['dosage_form'] == 'TABLET') {
        this.isTablet = true;
      } else {
        this.isTablet = false;
        this.getStages('Direct Compression');
      }
    }
  }*/

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

  updateBatchQty() {
    let materials = this.selectedBatch["materials"];
    for (let i = 0; i < materials.length; i++) {
      let material = materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else if (material['unit'] == "gm") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else if (material['unit'] == "ml") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000)).toFixed(2);
        material['batch_unit'] = "Litre";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / +this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      materials[i] = material;
    }
    this.selectedBatch["materials"] = materials;

    let lots = [];
    for (let i = 0; i < this.lots; i++) {
      lots[lots.length] = materials;
    }
    this.selectedBatch["lots"] = lots;
  }

  saveMaster(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    const formData = new FormData();

    let temp = data.value;
    for (let key in temp) {
      let value = temp[key];
      // Use `key` and `value`
      formData.append(key, value);
    }

    if (this.flow !== undefined) {
      formData.append('flow', this.flow, this.flow.name);
    }

    formData.append("materials", JSON.stringify(this.selectedBatch['materials']));
    formData.append("stages", JSON.stringify(this.selectedProduct['stages']));
    formData.append("instructions", JSON.stringify(this.instructions));

    /* temp['materials'] = this.selectedBatch['materials'];
    temp['stages'] = this.selectedProduct['stages'];
    temp['instructions'] = this.instructions; */
    this.service.post('production/master.php?type=saveMaster', formData
    ).subscribe(response => {
      if (response['status'] == 'success') {
        this.stages = [];
        this.packing = [];
        this.instructions = [];
        data.resetForm();
        alert('Saved Successfully');
        this.router.navigate(['master/']);
      } else {
        alert('An error occured, Please try again');
      }
    });
  }

  add(data) {
    if (!data.valid) {
      alert('Instruction required');
      return;
    }
    this.instructions[this.instructions.length] = data.value;
    data.resetForm();
  }

  del(index) {
    this.instructions.splice(index, 1);
  }

}
