import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  add_packing_material = false;
  results;
  packingList = [];
  packing_List_All = [];
  selectedResult = [];
  sub_types;
  dosage_form = '';
  unit_name = '';
  pack_size;
  product_code = '';
  status = '';
  raw_materials = [];
  materials;
  dosages;
  products;
  selectedMaterial = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSubMaterials();
    this.getUnitFormulas();
    this.getPackSize();
  }
  getPackSize() {
    this.service.get('common.php?type=getPackSizes').subscribe(response => {
      this.pack_size = response;
    });
  }
  getUnitFormulas() {
    this.service.get('production/unitformula.php?type=getUnitFormulaLog').subscribe(response => {
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
  viewPackingMaterial(index) {
    this.selectedResult = this.results[index];
    this.isView = false;
    this.add_packing_material = true;
    this.packing_List_All = JSON.parse(this.selectedResult['packing_materials']);
    if (this.packing_List_All == null) {
      this.packing_List_All = [];
    }
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.raw_materials = JSON.parse(this.selectedResult['raw_materials']);
    this.packing_List_All = JSON.parse(this.selectedResult['packing_materials']);
    if (this.packing_List_All == null) {
      this.packing_List_All = [];
    }
    for (let i = 0; i < this.raw_materials.length; i++) {
      let grade_name = '';
      let grade = this.raw_materials[i]['grade'];
      for (let j = 0; j < grade.length; j++) {
        grade_name = grade_name + grade[j]['grade'] + ' ';
      }
      grade_name = grade_name.trim().replace(' ', ',');
      this.raw_materials[i]['grade'] = grade_name;
    }
    this.isView = true;
  }

  download() {
    this.service.open('production/unitformula.php?type=downloadUnitFormla&id=' + this.selectedResult['id']);
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  getSubMaterials() {
    let value = 'Packing Material';
    let idx = 0;
    this.service.observableMaterialTypes.subscribe(response => {
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_type'] == value) {
          idx = i;
        }
      }
      this.sub_types = [];
      this.sub_types = response[idx]['sub_materials'];


    });

  }
  getMaterialsBySubType(value) {

    this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value + "&material_nature=").subscribe(response => {
      this.materials = response;
    });
  }
  getSelectedMaterial(index) {

    this.selectedMaterial = this.materials[index - 1];
    this.unit_name = this.selectedMaterial['unit'];


  }

  addPackingMaterial(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    if (Number(temp['overages']) > 0) {
      let percent = ((Number(temp['qty'] * temp['overages'])) / 100);
      percent = Number(temp['qty']) + percent;
      temp['total_qty'] = parseFloat(percent + '').toFixed(2);
    } else {
      temp['total_qty'] = temp['qty'];
    }
    temp['material_code'] = this.selectedMaterial['material_code'];
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['grade'] = this.selectedMaterial['grade'];
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }
  savePackingMaterial(form) {

    if (this.packingList.length == 0) {
      alertify.error('Please add packing material');
      return
    }
    if (!form.valid) {
      alertify.error('All Fields are mandatory');
      return
    }
    
    let temp = form.value;

    for(let x=0; x< this.packing_List_All.length; x++){
      if(this.packing_List_All[x]['pm_pack_size'] ==  temp['pack_size']['pack_size'] &&
         this.packing_List_All[x]['pm_pack_unit'] ==  temp['pack_size']['unit']){
          alertify.error(temp['pack_size']['pack_size'] +'-'+temp['pack_size']['unit']+' Already exists. Duplicates not allowed');
          return;
         }
    }


    temp['packing_materials'] = this.packingList;
    temp['pm_pack_size'] = temp['pack_size']['pack_size'];
    temp['pm_pack_unit'] = temp['pack_size']['unit'];


    let pack_list = {
      "id": this.packing_List_All.length + 1,
      "packing_list": this.packingList,
      "pm_pack_size": temp['pack_size']['pack_size'],
      "pm_pack_unit": temp['pack_size']['unit'],
      "pm_batch_size": temp['pm_batch_size']
    }

    this.packing_List_All.push(pack_list);
    let obj = {
      "packing_materials": this.packing_List_All,
      "pm_pack_size": temp['pack_size']['pack_size'],
      "pm_pack_unit": temp['pack_size']['unit'],
      "pm_batch_size": temp['pm_batch_size']
    }


    this.service.post('production/master.php?type=save_packing_material&id=' + this.selectedResult['id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Packing material details has been successfully!');
        this.isView = false;
        this.getUnitFormulas();
        this.add_packing_material = false;
        this.packingList = [];
        form.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
