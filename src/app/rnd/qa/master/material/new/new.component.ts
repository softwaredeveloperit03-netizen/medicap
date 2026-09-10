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

   
  units;
  isRawMaterial = true;
  isPackingMaterial = false;
  isChemicalMaterial = false;
  material;
  materialList=[];
  selectedMaterial;
  
  material_type = 'Raw Material';
  material_subtype = 'API';
  for_product = 'Yes';
  isprinted = 'Printed Foil';
  storage_condition = '';

  products;
  storage_conditions: any[] = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getUnits();
    this.getStorageConditions();
  }

  getStorageConditions(): void {
    this.service.get('master/master.php?type=get_storage_conditions').subscribe({
      next: (response) => {
        this.storage_conditions = Array.isArray(response) ? response : [];
        if (this.storage_conditions.length === 0) {
          this.loadStorageConditionsFallback();
        }
      },
      error: () => {
        this.loadStorageConditionsFallback();
      }
    });
  }

  private loadStorageConditionsFallback(): void {
    this.service.get('common.php?type=getStorageConditions').subscribe({
      next: (fallback) => {
        this.storage_conditions = Array.isArray(fallback) ? fallback : [];
      },
      error: () => {
        this.storage_conditions = [];
      }
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  checkMaterialType(material) {
    if (material === "Raw Material") {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = false;
    } else if (material === "Packing Material"){
      this.getProducts();
      this.isRawMaterial = false;
      this.isPackingMaterial = true;
      this.isChemicalMaterial = false;
    } else if(material === "Chemicals") {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = true;
    } else {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isChemicalMaterial = false;
    }
  }

  addMaterial(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['equivalent'] = this.materialList;
    temp['storage_condition'] = this.storage_condition || temp['storage_condition'] || '';

    this.service.post('rnd/qa/master/material.php?type=saveMaterial', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
        this.materialList = [];
        this.storage_condition = '';
        data.resetForm();
        this.router.navigate(['/rnd/qa/master/material']);
      } else {
        alertify.error(response['status'] || 'Failed: An error occured, please try again!');
      }
    });
  }
  
  addEquivalent(data){
    this.materialList[this.materialList.length]=data.value;
    data.resetForm();
  }

  selectMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.material[index];
    } else {
      this.selectedMaterial = [];
    }
  }
  
  del(index) {
    this.materialList.splice(index, 1);
  }
  
}
