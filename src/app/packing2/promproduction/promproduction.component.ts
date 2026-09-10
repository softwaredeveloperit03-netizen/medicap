import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-promproduction',
  templateUrl: './promproduction.component.html',
  styleUrls: ['./promproduction.component.css']
})
export class PromproductionComponent implements OnInit {

  isView = false;
  is_prepare_work_order = false;
  allocate_batch_no='Yes';
  is_view_batches = true;
  has_coated_batches = "No";
  is_view_work_order = false;
  no_of_lots_coated = 0;
  no_of_lots = 0;
  no_of_batches = 0;
  selected_batch_index = 1;
  is_data_generated = false;
  results;
  batch_results;
  overages_percent = 0;
  selectedResult = []; 
  selectedResults = [];
  raw_materials = [];
  packing_materials = [];
  work_order_raw_materials = [];
  actual_yeild = 0;
  stage_yield = 0;
  work_order_common_materials = [];
  work_order_coated_materials = [];
  work_order_un_coated_materials = [];

  work_order_lots = [];
  work_order_lot_materials = [];

  pack_sizes = [];
  coatd_work_order_lots = [];
  coatd_work_order_lot_materials = [];


  work_order_packing_materials = [];
  is_view_shortages = false;
  batches_list = [];
  from_date = '';
  to_date = '';
  today = '';
  product_name = '';
  software_type = '';
  showTailingBatches;
  plant_type = '';
  index;
  constructor(private service: DataAccessService) {
    // this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    // this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    // this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPlans();
    this.plant_type = this.service.getPlantConfigFields("plant_type")
    this.software_type = this.service.getPlantConfigFields("software_type")

  }

  getPlans() {
    this.results = [];
    this.pack_sizes = [];
    this.service.get('production/plan.php?type=getPlansForPacking&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;

    });
  }
  get_batch_plan_details() {
    this.service.get('production/plan.php?type=get_batch_plan_details&material_type=PM&batch_plan_id=' + this.selectedResult['id']).subscribe(response => {
      this.batch_results = response;
      if (this.batch_results == null) {
        this.batch_results = [];
      }
      this.no_of_batches = Number(this.selectedResult['total_batches']);
      let batches = (this.no_of_batches - this.batch_results.length);
      for (let j = 0; j < this.batch_results.length; j++) {
        this.batches_list.push(this.batch_results[j]);
      }
      for (let i = 0; i < batches; i++) {
        let obj = {
          "id": 0,
          "batch_id": (batches + (i + 1)),
          "plan_date": this.selectedResult['entry_date'],
          "batch_size": this.selectedResult['planned_batch_size'],
          "bfr_no": this.selectedResult['bfr_no'],
          "mfr_no": this.selectedResult['mfr_no'],
          "status": "Pending",
          "qa_person": "",
          "qa_date": ""
        };
        this.batches_list.push(obj);

      }
    });
  }

  view(index) {
    this.batches_list = [];
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];
    this.is_view_shortages = false;
    this.selectedResult = this.results[index];
    this.selectedResults = this.results[index]['pack_sizes'];
    this.actual_yeild = this.selectedResult['stage_yield'];
    this.isView = true;
    this.get_batch_plan_details();
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.work_order_raw_materials = this.selectedResult['raw_materials'];
    this.work_order_packing_materials = this.selectedResult['packing_material'];

  }

  download() {
    // this.service.open('production/bmr/plan.php?type=downloadPlans&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }
  clearWorkOrder() {
    this.packing_materials = this.selectedResult['packing_material'];
    this.actual_yeild = this.selectedResult['stage_yield'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.work_order_raw_materials = this.selectedResult['raw_materials'];
    this.work_order_packing_materials = this.selectedResult['packing_material'];
    this.work_order_lot_materials = [];
    this.work_order_lots = [];
    this.isView = true;
    this.is_view_batches = true;
    this.is_prepare_work_order = false;
  }

  prepareWorkOrder() {
    this.has_coated_batches = "No";
    this.pack_sizes = this.selectedResult['pack_sizes'];
    // this.packing_materials = this.selectedResult['packing_material'];
    // this.raw_materials = this.selectedResult['raw_materials'];
    // this.work_order_raw_materials = [];
    // this.calculateBatchOverages('')

    // this.work_order_packing_materials = this.selectedResult['packing_material'];
    this.isView = false;
    this.is_view_batches = false;
    this.is_view_shortages = false;
    this.is_prepare_work_order = true;
  }

  // viewWorkOrder(idx) {
  //   this.pack_sizes = this.selectedResult['pack_sizes'];
  //   this.isView = false;
  //   this.is_view_batches = false;
  //   this.is_view_shortages = false;
  //   this.is_view_work_order = true;
  //   this.calculateBatchOverages('');
  // }
  generateData(data) {
    this.is_data_generated = false;
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.calculateBatchOverages(data.value['no_of_lots']);

    this.is_data_generated = true;
  }


  segregate_materials() {
    this.work_order_coated_materials = [];
    this.work_order_un_coated_materials = [];
    this.work_order_common_materials = [];
    let temp = [...this.work_order_raw_materials];
    for (let i = 0; i < temp.length; i++) {
      let obj = temp[i];
      if (obj['split_into_lots'] == 'Yes') {
        if (obj['role'] == 'Coated') {

          this.work_order_coated_materials.push(obj);
        } else {
          this.work_order_un_coated_materials.push(obj);
        }
      }
      else {
        temp[i]['each_lot_qty'] = temp[i]['total_final_qty']
        this.work_order_common_materials.push(temp[i]);
      }
    }
  }

  split_lots(no_of_lots) {
    this.work_order_lots = [];
    this.coatd_work_order_lots = [];
    this.work_order_lot_materials = [];
    this.coatd_work_order_lot_materials = [];
    let temp = [...this.work_order_coated_materials];
    for (let j = 0; j < no_of_lots; j++) {
      for (let i = 0; i < temp.length; i++) {
        let obj = temp[i];
        this.coatd_work_order_lot_materials.push(obj);
      }
      if (this.coatd_work_order_lot_materials.length > 0) {
        let dataObj = {
          "lot_no": j + 1,
          "lots": this.coatd_work_order_lot_materials
        }
        this.coatd_work_order_lots.push(dataObj)
        this.coatd_work_order_lot_materials = [];
      }
    }

    temp = [...this.work_order_un_coated_materials];
    for (let j = 0; j < no_of_lots; j++) {
      for (let i = 0; i < temp.length; i++) {
        let obj = temp[i];
        this.work_order_lot_materials.push(obj);
      }
      if (this.work_order_lot_materials.length > 0) {
        let dataObj = {
          "lot_no": j + 1,
          "lots": this.work_order_lot_materials
        }
        this.work_order_lots.push(dataObj)
        this.work_order_lot_materials = [];
      }
    }




  }


  calculateBatchOverages(no_of_lots) {
    for (var j = 0; j < this.pack_sizes?.length; j++) {
      for (var i = 0; i < this.pack_sizes[j]['packing_material'].length; i++) {
        var batch_qty = Number(this.pack_sizes[j]['packing_material'][i]['batch_qty']);
        var batch_size = Number(this.selectedResult['batch_size']);
        this.actual_yeild
        let ovrages = parseFloat((batch_qty / batch_size) * this.actual_yeild + '').toFixed(2);
        this.pack_sizes[j]['packing_material'][i] = { ...this.pack_sizes[j]['packing_material'][i], total_final_qty: ovrages };
        this.pack_sizes[j]['packing_material'][i] = { ...this.pack_sizes[j]['packing_material'][i], pack_size_id: this.pack_sizes[j]['id'] };
        this.work_order_packing_materials[i]['total_final_qty'] = parseFloat((Number(this.work_order_packing_materials[i]['batch_qty']) * Number(ovrages)) + '').toFixed(2);
      }
    }
  }

  saveData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    if (!this.is_data_generated) {
      alertify.error('Please Generate Lod, Assay and Batch Overages');
      return;
    }
    this.packing_materials = [];
    for (var x = 0; x < this.pack_sizes?.length; x++) {
      for (var i = 0; i < this.pack_sizes[x]['packing_material'].length; i++) {

        this.packing_materials.push( this.pack_sizes[x]['packing_material'][i]);
      }
    }
    let dataObj = {
      "batch_plan_id": this.selectedResult['id'],
      "selected_batch_index": this.selected_batch_index,
      "ebmr_status": 'N/A',
      "lod_criteria": 'N/A',
      "assay_criteria": 'N/A',
      "batch_overages": '0',
      "overages_percent": '0',
      "calculation_type": '0',
      "no_of_lots": '1',
      "raw_materials": '[]',
      "packing_materials": this.packing_materials,      
      "coated_lots": '[]',
      "common_materails": '[]',
      'material_type': 'PM',
      'allocate_batch_no' : this.allocate_batch_no

    }
    this.service.post('production/workorder.php?type=save_pm_work_order', JSON.stringify(dataObj)).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Work Order has been send to Approval');
        this.isView = true;
        this.getPlans();
        this.get_batch_plan_details();
        this.is_prepare_work_order = false;
        this.is_view_batches = true;
      } else {
        alertify.error('Failed: ' + response['status']);
      }
    });
    console.log(JSON.stringify(dataObj));
  }

}

