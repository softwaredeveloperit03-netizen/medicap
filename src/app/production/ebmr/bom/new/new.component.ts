import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  open = true;
  model = {"name": "test"}
  formPageOneValid = false;
  formPageThreeValid = false;

  dosage_form = '';
  dosages;
  products;

  selectedResult = [];
  selectedMaterial = [];
  isProduct= false;
  results;
  grades;
  selectedProduct =[];
  selectedMFR =[];
  batch_size = 0;
  pack_batch = 500;
  mfrs =[];
  raw =[];
  packing =[];
  unitformulas;
  mfr_no ='';
  product_code ='';
  lot = 0;
  stage =[];
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDosages();
    this.getGrades();
    this.getUnitFormulas();
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
      console.log('fd', this.dosages);
    });
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getProductsByDosage(dosage_form) {
    this.service.get('production/bom.php?type=getProductsByUnits&dosage_form=' + dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  getUnitFormulas(){
    this.service.get('production/unitformula.php?type=getUnitFormulas').subscribe(response => {
      this.unitformulas = response;
    });
  }

  getDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.products[index];
      this.mfrs = this.selectedResult['mfrs'];
      
    }
  }

  getMaterials(index){
    index = index - 1;
    if(index !== -1){
      this.selectedMaterial = this.mfrs[index];
      this.raw = this.selectedMaterial['raw_materials'];
      this.packing = this.selectedMaterial['packing_materials'];
      this.stage = this.selectedResult['stages']
      console.log('raw',this.raw);

      this.isProduct=true;
    }
  }

onChange(event, index) {
    let stages = this.stage[index];
    if (event) {
      stages['selected'] = true;
      console.log('stagestrue',stages);
    }
    this.stage[index] = stages;
    
  }

  calculate() {
    console.log('batxh',this.batch_size);
    let raw_materials = this.raw;
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
      raw_materials[i] = material;
    }
    this.raw = raw_materials;

    let packing_materials = this.packing;
    for (let i = 0; i < packing_materials.length; i++) {
      let material = packing_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat(((material["qty"] / this.pack_batch) * 100000 ) + '').toFixed(2)
        console.log('qtyy', material["batch_qty"]);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = +parseFloat(((material["qty"] / this.pack_batch) * 100000 ) + '').toFixed(2)
        console.log('qtyy', this.pack_batch);
        material['batch_unit'] = material['unit'];
      }

        let batch_qty = +material["batch_qty"];

      packing_materials[i] = material;
    }
    this.packing = packing_materials;
  }

  save(data){
    let temp = data.value;
    temp['raw_materials'] = this.raw;
    temp['packing_materials'] = this.packing;
    temp['mfr_no'] = this.mfr_no;
    temp['batch_size'] = this.batch_size;
    temp['product_code'] = this.product_code;
    temp['lots'] = this.lot;
    temp['batch_stages'] = this.stage;
    this.service.post('production/bom.php?type=saveBatchFormula',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] == 'success'){
        alert('Data Save Sucessfuly');
        this.router.navigate(['/production/ebmr/bom']);
        data.resetForm();
      }else{
        alert('Some error Occured');
      }
    });
  }

}
