import { Component, OnInit } from '@angular/core';
import { FormBuilder } from '@angular/forms';
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
  materials;
  units;
  processes;
  packings;
  grades;
  selectedProd = [];
  materialList = [];
  packingList = [];

  additionalList = [];

  average_weight = 0;
  formula_weight = 0;
  qty = 0;
  unit = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    // this.getApprovedProducts();
    this.getUnits();
    this.getDosages();
    this.getGrades();
  }

  // getApprovedProducts() {
  //   this.service.get('production/unitformula.php?type=getApprovedProducts').subscribe(response => {
  //     this.products = response;
  //   });
  // }

  getApprovedRawMaterials(value) {
    this.service.get('production/unitformula.php?type=getRawMaterials&material_type='+ value).subscribe(response => {
      this.materials = response;
    });
  }

  getUnits() {
    this.service.get('production/unitformula.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getMaterial(value){
    this.service.get('production/unitformula.php?type=getPackingMaterials&material_type=' +value).subscribe(response => {
      this.packings = response;
    });
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
      console.log('fd', this.dosages);
    });
  }

  getProductsByDosage(dosage_form) {
    this.service.get('bmr/mfr.php?type=getProductsByDosage&dosage_form=' + dosage_form).subscribe(response => {
      this.products = response;
    });
  }


  getProdDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedProd = this.products[index];
      this.processes = this.selectedProd['stages'];
    }
  }

  selectedMaterial = [];
  getSelectedMaterial(index) {
    index = index - 1;
    this.selectedMaterial = this.materials[index];

    let equivalents = this.selectedProd['equivalents'];
    for (let  i =0; i < equivalents.length; i++) {
      let equivalent = equivalents[i];
      if (equivalent['material_code'] == this.selectedMaterial['material_code']) {
        this.qty = +equivalent['label_claim'] * +this.selectedMaterial['factor'];
        this.unit = equivalent['unit'];
      }
    }
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    let temp_wt = this.formula_weight;
    temp_wt += +temp['qty'];

    if (temp['yield_contributing'] == 'Yes') {
      if (this.average_weight >= temp_wt) {
        this.formula_weight += +temp['qty'];
        this.materialList[this.materialList.length] = data.value;
        data.resetForm();
      } else {
        alert('Average Weight Not Matching, Please check!');
      }
    }
  }

  dele(index) {
    this.materialList.splice(index, 1);
  }

  addAdditional(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.additionalList[this.additionalList.length] = data.value;
    data.resetForm();
  }

  delAdditional(index) {
    this.additionalList.splice(index, 1);
  }

  addPage(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }


  del(index) {
    this.packingList.splice(index, 1);
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    if (this.materialList.length == 0) {
      alert('Materials are required');
      return;
    }
    if (this.packingList.length == 0) {
      alert('Packings are required');
      return;
    }
    let temp = data.value;
    temp['raw_materials'] = this.materialList;
    temp['additional_materials'] = this.additionalList;
    temp['packing_materials'] = this.packingList;
    this.service.post('production/unitformula.php?type=saveUnitFormula', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record saved successfully');
        data.resetForm();
        this.materialList = [];
        this.packingList = [];
        this.additionalList = [];
        this.selectedProd = [];
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }


}
