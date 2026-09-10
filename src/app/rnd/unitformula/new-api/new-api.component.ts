import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new-api',
  templateUrl: './new-api.component.html',
  styleUrls: ['./new-api.component.css']
})
export class NewApiComponent implements OnInit {
  product_type = '';
  products;
  units;
  master_formula = 'New';
  fg_sub_materials =[];
  materials;
  packings;
  sub_types;
  qty_overages_qty=0;
  selected_product = null;
  batch_size = 0;
  materialList = [];
  packingList = [];
  selecteddosage = [];
  amaterials;
  dosages;
  dosage_form = '';
  unit = '';
  stageListadd = [];
  selectedPacking = [];
  grades;
  grade = ''
  selectedMaterial = [];
  product_grade = '';
  percent_qty;
  min_per = 0;
  max_per = 0;
  min_output_qty;
  revisionList = [];
  max_output_qty;
  plant_type='';
  input_qty_tally = 0;
  
  fg_sizes = [];
  fg_shapes = [];
  fg_sub_types=[];
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getSubMaterials();
    this.getGrades()
    this.getUnits();
    this.plant_type = this.service.getPlantConfigFields('plant_type');

  }

  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
  getGrades() {
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
    });
  }
  getProductsByDosage(value) {
    this.service.get('common.php?type=getProductsByDosage&product_type=' + value).subscribe(response => {
      this.products = response;
    });
  }

  getApprovedRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe(response => {
      this.materials = response;
    });
  }

  getSelectedMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
    }
  }

  getSubMaterials() {
    let value = 'Raw Material';
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

  getMaterial(value) {
    this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe(response => {
      this.packings = response;
    });
  }

  getPackings(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedPacking = this.packings[index];
    }
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (Number(this.batch_size) == 0) {
      alertify.error('Please enter batch size');
      return;
    }
    let temp = data.value;
    let bqty = 0;

    if (Number(temp['overages_per']) > 0) {
      temp['overage_qty'] = parseFloat(((Number(data.value['qty']) * Number(temp['overages_per'])) / 100) + '').toFixed(2);
    } else {
      temp['overage_qty'] = "0";
    }
    temp['total_qty'] = Number(temp['overage_qty']) + Number(temp['qty']);


    if (temp['yeild_contribution'] == 'Yes') {
      bqty = Number(this.input_qty_tally) + Number(data.value['qty']) + Number(temp['overage_qty']);
      if (bqty > Number(this.batch_size)) {
        alertify.error('Batch size not tally');
        return;
      }
    }

    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['material_code'] = this.selectedMaterial['material_code'];
    this.materialList[this.materialList.length] = temp;
    if (temp['yeild_contribution'] == 'Yes') {
      this.input_qty_tally = Number(this.input_qty_tally) + Number(data.value['total_qty']);
    }
    data.resetForm();
    this.materials=[];
  }

  dele(index) {
    this.materialList.splice(index, 1);
    let qty=0;
    for(let i=0; i<this.materialList.length;i++){
      qty = Number(qty)+Number(this.materialList[i]['qty_overages_qty']);
    }
    this.input_qty_tally = qty;
  }

  addPage(data) {
    if (Number(this.batch_size) == 0) {
      alertify.error('Please enter batch size');
      return;
    }
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let bqty = Number(this.input_qty_tally) + Number(data.value['qty_overages_qty']);
    if (bqty > Number(this.batch_size)) {
      alertify.error('Batch size not tally');
      return;
    }
    let temp = data.value;
    temp['material_name'] = this.selectedPacking['material_name'];
    this.packingList[this.packingList.length] = temp;
    this.input_qty_tally = bqty;
    data.resetForm();
  }

  deletestage(index) {
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
    if (Number(this.batch_size) != Number(this.input_qty_tally)) {
      alertify.error('Batch size not tally');
      return;
    }
    let temp = data.value;
    if (temp['bom_type'] == 'Fresh Batch') {
      if (this.materialList.length == 0) {
        alertify.error('Raw Materials are required');
        return;
      }
    }

    temp['raw_materials'] = this.materialList;
    temp['packing_materials'] = [];
    temp['product_type'] = temp['product_type'];
    temp['unit'] = this.unit;
    this.service.post('production/master.php?type=saveMFR', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product MFR initiated successfully!');
        this.router.navigate(['/planning/unitformula']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


  calculate() {
    //Min Yeild
    let percent = (Number(this.batch_size) * Number(this.min_per)) / 100

    this.min_output_qty = parseFloat(percent + '').toFixed(2);

    //Max Yeild
    percent = (Number(this.batch_size) * Number(this.max_per)) / 100

    this.max_output_qty = parseFloat(percent + '').toFixed(2);

  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  LoadPoduct(idx) {
    this.selected_product = this.products[idx];
  }
  calcPercentage(data) {
    let temp = data.value;   

    if (Number(temp['overages_per']) > 0) {
      temp['qty_overages_qty'] = parseFloat(((Number(data.value['qty']) * Number(temp['overages_per'])) / 100) + '').toFixed(2);
    } else {
      temp['qty_overages_qty'] = "0";
    }
   this.qty_overages_qty = Number(temp['qty_overages_qty']) + Number(temp['qty']);

    this.percent_qty = parseFloat(((this.qty_overages_qty / Number(this.batch_size)) * 100) + '').toFixed(2);;
  }

  getFGMaterials(value) {
    this.service.observableFGTypes.subscribe(response => {
      // let data =response;
      if (value == "") {
        return;
      }
      let idx = 0;
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_subtype'] == value) {
          idx = i;
        }
      }
      let data = response[idx]
      this.fg_sub_materials = data['sub_materials']; 
    })
  }

  getSizeAndShape(value) {
    let idx = 0;
    let data = null;
    this.fg_sizes = [];
    this.fg_shapes = [];
    this.fg_sub_types=[];
    for (let i = 0; i < this.fg_sub_materials.length; i++) {
      if (this.fg_sub_materials[i]['dosage_form'] == value) {
        data = this.fg_sub_materials[i];
      }
    }
    if (data != null) {
      this.fg_sizes = JSON.parse(data['dosage_sizes']);
      this.fg_shapes = JSON.parse(data['dosage_shapes']);
      this.fg_sub_types = JSON.parse(data['dosage_sub_form']);
    }
  }
}
