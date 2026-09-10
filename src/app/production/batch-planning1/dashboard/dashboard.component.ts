import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {
  is_view_batches = true;
  is_prepare_work_order = false;
  is_view_work_order = false;
  no_of_batches = 0;
  isView = false;
  selected_batch_index = 1;
  is_data_generated = false;
  results;
  batch_results;
  overages_percent = 0;
  selectedResult = [];
  raw_materials = [];
  packing_materials = [];
  work_order_raw_materials = [];
  work_order_packing_materials = [];
  is_view_shortages = false;
  batches_list = [];
  from_date = '';
  to_date = '';
  today = '';
  product_name = '';
  showTailingBatches;
  plant_type='';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPlans();
    this.plant_type = this.service.getPlantConfigFields("plant_type")

  }

  getPlans() {
    this.results=[];
    this.service.get('production/plan.php?type=getPlans&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  get_batch_plan_details() {
    this.service.get('production/plan.php?type=get_batch_plan_details&batch_plan_id=' + this.selectedResult['id']).subscribe(response => {
      this.batch_results = response;

      this.no_of_batches = Number(this.selectedResult['total_batches']);
      let batches = (this.no_of_batches - this.batch_results.length);
      for(let j=0;j< this.batch_results.length;j++){
        this.batches_list.push( this.batch_results[j]);
      }
      for (let i = 0; i < batches; i++) {
        let obj = {
          "id":0,
          "batch_id" : (batches+(i+1)),
          "plan_date": this.selectedResult['entry_date'],
          "batch_size": this.selectedResult['planned_batch_size'],
          "bfr_no": this.selectedResult['bfr_no'],
          "mfr_no": this.selectedResult['mfr_no'],          
          "status" : "Pending",          
          "qa_person":"",
          "qa_date" :""
        };
        this.batches_list.push(obj);
        this.isView = true;
      }
    });
  }
  view(index) {
    this.batches_list = [];
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];
    this.is_view_shortages = false;
    this.selectedResult = this.results[index];
    this.get_batch_plan_details();
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.work_order_raw_materials = this.selectedResult['raw_materials'];
    this.work_order_packing_materials = this.selectedResult['packing_material'];

  }

  download() {
    this.service.open('production/bmr/plan.php?type=downloadPlans&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }
  prepareWorkOrder() {
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.work_order_raw_materials = this.selectedResult['raw_materials'];
    this.work_order_packing_materials = this.selectedResult['packing_material'];
    this.isView = false;
    this.is_view_batches = false;
    this.is_view_shortages = false;
    this.is_prepare_work_order = true;
  }

  viewWorkOrder(idx) {
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = []
    for(let i=0;i< this.batch_results[idx]['materials'].length;i++){
      if(this.batch_results[idx]['materials'][i]['material_type']=='Raw Material'){
        this.work_order_raw_materials.push(this.batch_results[idx]['materials'][i]);
      }else{
        this.work_order_packing_materials.push(this.batch_results[idx]['materials'][i]);
      }
    }
    this.isView = false;
    this.is_view_batches = false;
    this.is_view_shortages = false;
    this.is_view_work_order = true;
  }
  generateData(data) {
    this.is_data_generated = false;
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.calculateBatchOverages();
    for (let i = 0; i < this.work_order_raw_materials.length; i++) {

      this.work_order_raw_materials[i]['lod_status'] = data.value['lod_criteria'] == 'As Such Basis' ? 'No' : 'Yes';
      this.work_order_raw_materials[i]['assay_status'] = data.value['assay_criteria'] == 'As Such Basis' ? 'No' : 'Yes';
    }
    this.is_data_generated = true;
  }



  calculateBatchOverages() {
    for (let i = 0; i < this.work_order_raw_materials.length; i++) {
      let ovrages = parseFloat(((Number(this.work_order_raw_materials[i]['batch_qty']) * Number(this.overages_percent)) / 100) + '').toFixed(2);
      this.work_order_raw_materials[i]['batch_overages'] = parseFloat(this.overages_percent + '').toFixed(2);
      this.work_order_raw_materials[i]['total_final_qty'] = parseFloat((Number(this.work_order_raw_materials[i]['batch_qty']) + Number(ovrages)) + '').toFixed(2);
    }
    for (let i = 0; i < this.work_order_packing_materials.length; i++) {
      let ovrages = parseFloat(((Number(this.work_order_packing_materials[i]['batch_qty']) * Number(this.overages_percent)) / 100) + '').toFixed(2);
      this.work_order_packing_materials[i]['batch_overages'] = parseFloat(this.overages_percent + '').toFixed(2);
      this.work_order_packing_materials[i]['total_final_qty'] = parseFloat((Number(this.work_order_packing_materials[i]['batch_qty']) + Number(ovrages)) + '').toFixed(2);
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
    let dataObj = {
      "batch_plan_id": this.selectedResult['id'],
      "selected_batch_index": this.selected_batch_index,
      "ebmr_status": data.value['ebmr_status'],
      "lod_criteria": data.value['lod_criteria'],
      "assay_criteria": data.value['assay_criteria'],
      "batch_overages": data.value['batch_overages'],
      "overages_percent": data.value['overages_percent'],
      "raw_materials": this.work_order_raw_materials,
      "packing_materials": this.work_order_packing_materials,
      'material_type':'RM'

    }
    this.service.post('production/workorder.php?type=save_work_order', JSON.stringify(dataObj)).subscribe(response => {
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
