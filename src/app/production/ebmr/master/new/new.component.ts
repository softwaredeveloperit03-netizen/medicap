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

  product_type = '';
  products;
  units;

  selectedResult = [];
  materials;
  processes;
  packings;
  formula_for='';
  selectedProd = [];
  materialList = [];
  packingList = [];
  selecteddosage=[];
  amaterials;
  dosages;
  dosage_form='';
  equipments;
  unit='';
  selectedEquipment=[];
  // name = 'Angular';
  // list : any[];
  stageListadd=[];
  selectedPacking=[];

  selectedEquipments = [];
  selectedMaterial=[];
  batchStages = [];
  shareCheckedList(item:any[]){
    this.selectedEquipments = item;
    console.log(item);
  }
  shareIndividualCheckedList(item:{}){
    console.log(item);
  }
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getUnits();
    this.getEquipment();
  }

  getProductsByDosage(value) {
    this.service.get('production/master.php?type=getProductsByDosage&product_type=' + value).subscribe(response => {
      this.products = response;
    });
  }
  getEquipment(){
    this.service.get('equipments.php?type=getEquipmentTypes').subscribe(response => {
      this.equipments = response;

      for (let i = 0; i < this.equipments.length; i++) {
        let equipment = this.equipments[i];
        equipment['name'] = equipment['equipment_type'];
        equipment['checked'] = false;
        this.equipments[i] = equipment;
      }
    });
  }

  getDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedResult = this.products[index];
    }
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getApprovedRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype='+ value).subscribe(response => {
      this.materials = response;
    });
  }

  getSelectedMaterial(index){
    this.selectedMaterial=this.materials[index];
    console.log('material',this.selectedMaterial);
  }

  // getAdditionalRawMaterials(value) {
  //   this.service.get('common.php?type=getMaterialsByType&material_type='+ value).subscribe(response => {
  //     this.amaterials = response;
  //   });
  // }

  getMaterial(value){
    this.service.get('common.php?type=getMaterialsByType&material_subtype=' +value).subscribe(response => {
      this.packings = response;
    });
  }

  getPackings(index){
    this.selectedPacking = this.packings[index];
    console.log('packing',this.selectedPacking);
  }

  getProdDetails(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedProd = this.products[index];
      this.processes = this.selectedProd['stages'];
    }
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value;
    temp['material_name']=this.selectedMaterial['material_name'];
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
  }


  addStage(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.equipments.length; i++) {
      let equipment = this.equipments[i];
      if (equipment['checked'] == true) {
        test[test.length] = equipment;
      }
    }
    temp['equipments'] = test;
    this.stageListadd[this.stageListadd.length]= temp;
    data.reset();
  }

  dele(index) {
    this.materialList.splice(index, 1);
  }

  addPage(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    temp['material_name']=this.selectedPacking['material_name'];
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }

  deletestage(index){
    this.stageListadd.splice(index, 1);
  }
  del(index) {
    this.packingList.splice(index, 1);
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    if (this.materialList.length == 0) {
      alertify.error('Materials are required');
      return;
    }
    if (this.packingList.length == 0) {
      alertify.error('Packings are required');
      return;
    }
    let temp = data.value;
    temp['raw_materials'] = this.materialList;
    temp['packing_materials'] = this.packingList;

    let stages = this.stageListadd;
    for (let  i = 0; i < stages.length; i++) {
      let stage = stages[i];
      let equipments = stage['equipments'];
      for (let  j = 0; j < equipments.length; j++) {
        let equipment = equipments[j];
        equipment['equipments'] = [];
        equipments[j] = equipment;
      }
      stage['equipments'] = equipments;
      stages[i] = stage;
    }

    temp['stages'] = stages;
    temp['unit']=this.unit;
    this.service.post('production/master.php?type=saveMFR', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product MFR initiated successfully!');
        this.router.navigate(['/production/ebmr/master']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



  

}
