import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-bom',
  templateUrl: './bom.component.html',
  styleUrls: ['./bom.component.css']
})
export class BomComponent implements OnInit {

  isView = false;
  results;
  batch_size = 0;
  selectedResult = [];

  dosage_form = '';
  product_code = '';
  status = '';

  dosages;
  products;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getUnitFormulas();
    this.getDosages();
  }

  getUnitFormulas(){
    this.service.get('production/bom.php?type=getPendingBatchFormulas&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code + '&status=' + this.status).subscribe(response => {
      this.results = response;
    });
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts() {
    this.service.get('common.php?type=getProductsByDosage&dosage_form=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true; 
  }

  calculate() {
    console.log('batxh',this.batch_size);
    let raw_materials = this.selectedResult['raw_materials'];
    for (let i = 0; i < raw_materials.length; i++) {
      let material = raw_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.batch_size / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

       let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];

      raw_materials[i] = material;
    }
    this.selectedResult['raw_materials'] = raw_materials;

    let additional_materials = this.selectedResult['additional_materials'];
    for (let i = 0; i < additional_materials.length; i++) {
      let material = additional_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.batch_size / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

       let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];

      additional_materials[i] = material;
    }
    this.selectedResult['additional_materials'] = additional_materials;

    let packing_materials = this.selectedResult['packing_materials'];
    for (let i = 0; i < packing_materials.length; i++) {
      let material = packing_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.batch_size / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

        let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];

      packing_materials[i] = material;
    }
    this.selectedResult['packing_materials'] = packing_materials;
  }


  saveStandardBatchFormula(){
    let temp={};
    // temp['raw_materials']=this.batchraw;
    // temp['packing_materials']=this.batchList;
    // temp['additional_materials']=this.addtionBatchList;
    temp['raw_materials']=this.selectedResult['raw_materials'];
    temp['packing_materials']=this.selectedResult['packing_materials'];
    temp['additional_materials']=this.selectedResult['additional_materials'];
    this.service.post('production/bom.php?type=saveStandardBatchSize&id=' + this.selectedResult['id']+'&batch_size='+this.batch_size, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getUnitFormulas();
        alert('Batch Formula Added Successsfuly');
      }
    });
  }

}
