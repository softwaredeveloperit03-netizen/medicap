import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MEDICAP_PRODUCTION_FLOW, offerNextStep } from 'src/app/shared/medicap-production-flow';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  prepare_batch = false;
  isView = false;
  results;
  packingList = [];
  selectedResult = [];
  packing_List_All = [];
  sub_types;
  dosage_form = '';
  product_code = '';
  status = '';
  raw_materials = [];
  materials;
  avg_unit_weight = 1;
  dosages;
  products;
  plant_type = '';
  plant_id:any;
  selectedMaterial = [];
  constructor(private service: DataAccessService) { 

    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  ngOnInit() {
    this.getSubMaterials();
    this.getUnitFormulas();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    //this.getDosages();
  }

  getUnitFormulas() {
    this.service.get('production/unitformula.php?type=get_approved_UnitFormulas').subscribe(response => {
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

  view(index, is_prepare_batch) {
    this.selectedResult = this.results[index];
    this.avg_unit_weight = Number(this.selectedResult['average_weight']);
    // API returns raw_materials already decoded; older responses send the JSON string.
    const rawMaterials = this.selectedResult['raw_materials'];
    this.raw_materials = typeof rawMaterials === 'string'
      ? JSON.parse(rawMaterials || '[]')
      : (rawMaterials || []);
    // this.packing_List_All = JSON.parse(this.selectedResult['packing_materials']);
    this.packing_List_All =(this.selectedResult['packing_configuration']);
    if (this.packing_List_All == null) {
      this.packing_List_All = [];
    }
    // for (let i = 0; i < this.raw_materials.length; i++) {
    //   let grade_name = '';
    //   let grade = this.raw_materials[i]['grade'];
    //   for (let j = 0; j < grade.length; j++) {
    //     grade_name = grade_name + grade[j]['grade'] + ' ';
    //   }
    //   grade_name = grade_name.trim().replace(' ', ',');
    //   this.raw_materials[i]['grade'] = grade_name;
    // }
    this.isView = true;
    this.prepare_batch = is_prepare_batch;


    this.groupedMaterials = this.raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});


    
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
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
      if (this.selectedMaterial['unit'] = '') {
        this.selectedMaterial['unit'] = 'kg';
      }
    }
  }

  addPackingMaterial(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }
  batch_qty;
  groupedMaterials =[];
  calculateBatchQty(batch_size, mat_type) {
    this.groupedMaterials =[];
    
    if (mat_type == 'RM') {
      var rm_batch_size : Number =batch_size;
      for (let i = 0; i < this.raw_materials.length; i++) {
        let batch_qty = 0;
        if (this.plant_type != 'Formulation') {

          var batch_qty1 = (Number(this.raw_materials[i]['total_qty']) * Number(batch_size)) / Number(this.selectedResult['batch_size']);
          var f_batch_qty: Number = (+batch_qty1 );
          batch_qty = (+f_batch_qty );
          this.raw_materials[i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
        } else {
   
          let avgUnit_weight = 1;
          if (this.selectedResult['formula_for'] != 'Unit') {
            avgUnit_weight = Number(this.avg_unit_weight);
          }
          var final_qty_mg: Number = (Number(this.raw_materials[i]['total_qty']) * Number(batch_size)) / avgUnit_weight;
          var f_batch_qty: Number = (+final_qty_mg );
          batch_qty = (+f_batch_qty );
          this.raw_materials[i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
        }
            
      }
    } else {
      for (let j = 0; j < this.packing_List_All.length; j++) {
        for (let i = 0; i < this.packing_List_All[j]['packing_list'].length; i++) {
          let batch_qty;
          if (this.plant_type != 'Formulation') {
            batch_qty = (Number(this.packing_List_All[j]['packing_list'][i]['total_qty']) / Number(this.packing_List_All[j]['pm_batch_size'])) * Number(batch_size);
          } else {
            batch_qty = (Number(this.packing_List_All[j]['packing_list'][i]['total_qty']) / Number(this.packing_List_All[j]['pm_batch_size'])) * Number(batch_size);
          }
          this.packing_List_All[j]['packing_list'][i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
        }
      }
    }

 

    this.groupedMaterials = this.raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});


     
    console.log(this.raw_materials);
 


  }
  save(data) {
    const invalid = [];
    const controls = data.controls;
    for (const name in controls) {
      if (controls[name].invalid) {
        invalid.push(name);
      }
    } 
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }

    if (this.raw_materials.length == 0) {
      alertify.error('Raw Materials are required');
      return;
    }
    let temp = data.value;

    temp['mfr_no'] = this.selectedResult['mfr_no']
    temp['product_code'] = this.selectedResult['product_code']
    temp['raw_materials'] = this.raw_materials;
    temp['packing_materials'] = this.packing_List_All;
    temp['product_type'] = 'Raw Material';    
    temp['rm_batch_size_unit'] = this.selectedResult['batch_size_uom'] ;
    this.service.post('planning/raw.php?type=save_batch_formula', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product Batch Formula has been saved successfully!');
        this.isView = false;
        offerNextStep(
          MEDICAP_PRODUCTION_FLOW.batchFormulaLogApproval,
          'Planning → Batch Formula Log (For Approval tab)'
        );
      } else {
        alertify.error(response['status']);
       // alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
