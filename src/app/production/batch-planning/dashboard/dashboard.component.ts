import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MEDICAP_PRODUCTION_FLOW, offerNextStep } from 'src/app/shared/medicap-production-flow';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe],
})
export class DashboardComponent implements OnInit {
  is_view_batches = true;
  has_coated_batches = 'No';
  is_prepare_work_order = false;
  is_view_work_order = false;
  no_of_lots_coated = 0;
  no_of_lots = 0;
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

  work_order_common_materials = [];
  work_order_coated_materials = [];
  work_order_un_coated_materials = [];

  work_order_lots = [];
  work_order_lot_materials = [];

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
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
     this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPlans();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.software_type = this.service.getPlantConfigFields('software_type');
    this.get_rights();
  }

  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
  getPlans() {
    this.results = [];
    this.service
      .get(
        'production/plan.php?type=getPlans&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  get_batch_plan_details() {
    this.service
      .get(
        'production/plan.php?type=get_batch_plan_details&material_type=RM&batch_plan_id=' +
          this.selectedResult['id']
      )
      .subscribe((response) => {
        this.batch_results = response;
        if (this.batch_results == null) {
          this.batch_results = [];
        }
        this.no_of_batches = Number(this.selectedResult['total_batches']);
        let batches = this.no_of_batches - this.batch_results.length;
        for (let j = 0; j < this.batch_results.length; j++) {
          this.hydrateMaterialQtyFields(this.batch_results[j]['materials']);
          this.batches_list.push(this.batch_results[j]);
        }
        for (let i = 0; i < batches; i++) {
          let obj = {
            id: 0,
            batch_id: batches + (i + 1),
            plan_date: this.selectedResult['entry_date'],
            batch_size: this.selectedResult['planned_batch_size'],
            bfr_no: this.selectedResult['bfr_no'],
            mfr_no: this.selectedResult['mfr_no'],
            status: 'Pending',
            qa_person: '',
            qa_date: '',
            lod_status: '',
            assay_status: '',
          };
          this.batches_list.push(obj);
        }
      });
  }

  isIncompleteMfgWorkOrder(comp: any): boolean {
    if (!comp || !(Number(comp.id || 0) > 0)) {
      return true;
    }
    const lod = String(comp.lod_status || '').trim().toLowerCase();
    const lots = String(comp.no_of_lots ?? '').trim();
    const preparedLod =
      lod === 'as such basis' || lod === 'lod basis' || lod === 'assay basis';
    return !preparedLod && (lots === '' || lots === '0');
  }

  canPrepareMfgWorkOrder(comp: any): boolean {
    return true;
  }

  hydrateMaterialQtyFields(list: any): void {
    if (!Array.isArray(list)) {
      return;
    }
    for (const m of list) {
      const qty = m.qty ?? m.unit_qty ?? '';
      const batchQty = m.batch_qty ?? qty;
      if (m.overages === null || m.overages === undefined || m.overages === '') {
        m.overages = 0;
      }
      if (m.batch_overages === null || m.batch_overages === undefined || m.batch_overages === '') {
        m.batch_overages = 0;
      }
      if (m.total_qty === null || m.total_qty === undefined || m.total_qty === '') {
        m.total_qty = qty;
      }
      if (m.total_unit_qty === null || m.total_unit_qty === undefined || m.total_unit_qty === '') {
        m.total_unit_qty = m.total_qty ?? qty;
      }
      if (m.total_final_qty === null || m.total_final_qty === undefined || m.total_final_qty === '') {
        m.total_final_qty = batchQty;
      }
      if (m.total_batch_qty === null || m.total_batch_qty === undefined || m.total_batch_qty === '') {
        m.total_batch_qty = m.total_final_qty ?? batchQty;
      }
      if (!m.lod_status) {
        m.lod_status = 'No';
      }
      if (!m.assay_status) {
        m.assay_status = 'No';
      }
      if (!m.stage) {
        m.stage = 'General';
      }
    }
  }
  groupedMaterials = [];
  view(index) {
    this.batches_list = [];
    this.groupedMaterials = [];
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];
    this.is_view_shortages = false;
    this.selectedResult = this.results[index];
    this.isView = true;
    this.get_batch_plan_details();
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'] || [];
    this.hydrateMaterialQtyFields(this.raw_materials);
    this.work_order_raw_materials = this.raw_materials;
    this.work_order_packing_materials = this.selectedResult['packing_material'];
    this.hydrateMaterialQtyFields(this.work_order_packing_materials);

    this.groupedMaterials = this.work_order_raw_materials.reduce(
      (group, material) => {
        const { stage } = material;
        group[stage] = group[stage] ?? [];
        group[stage].push(material);
        return group;
      },
      {}
    );
  }

  download() {
    this.service.open(
      'production/bmr/plan.php?type=downloadPlans&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
  clearWorkOrder() {
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.work_order_raw_materials = this.selectedResult['raw_materials'];
    this.work_order_packing_materials = this.selectedResult['packing_material'];
    this.work_order_lot_materials = [];
    this.work_order_lots = [];
    this.isView = true;
    this.is_view_batches = true;
    this.is_prepare_work_order = false;
  }
  prepareWorkOrder(comp: any = null) {
    this.has_coated_batches = 'No';
    if (comp && Number(comp.batch_id || 0) > 0) {
      this.selected_batch_index = Number(comp.batch_id);
    }
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'] || [];
    this.hydrateMaterialQtyFields(this.raw_materials);
    this.work_order_raw_materials = this.raw_materials;
    for (let i = 0; i < this.work_order_raw_materials.length; i++) {
      if (this.work_order_raw_materials[i]['role'] == 'Coated') {
        this.has_coated_batches = 'Yes';
      }
    }

    this.work_order_packing_materials =
      (this.selectedResult['packing_configuration'] &&
        this.selectedResult['packing_configuration'][0] &&
        this.selectedResult['packing_configuration'][0]['packing_materials']) ||
      [];
    this.hydrateMaterialQtyFields(this.work_order_packing_materials);
    this.isView = false;
    this.is_view_batches = false;
    this.is_view_shortages = false;
    this.is_prepare_work_order = true;
    this.groupedMaterials = this.work_order_raw_materials.reduce(
      (group, material) => {
        const { stage } = material;
        group[stage] = group[stage] ?? [];
        group[stage].push(material);
        return group;
      },
      {}
    );
  }

  viewWorkOrder(idx) {
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];
    const materials =
      this.batch_results && this.batch_results[idx]
        ? this.batch_results[idx]['materials'] || []
        : [];
    for (let i = 0; i < materials.length; i++) {
      if (
        materials[i]['material_type'] ==
        'Raw Material'
      ) {
        this.work_order_raw_materials.push(materials[i]);
      } else {
        this.work_order_packing_materials.push(materials[i]);
      }
    }
    if (this.work_order_raw_materials.length === 0) {
      this.work_order_raw_materials = [
        ...(this.selectedResult['raw_materials'] || []),
      ];
    }
    this.hydrateMaterialQtyFields(this.work_order_raw_materials);
    this.hydrateMaterialQtyFields(this.work_order_packing_materials);
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
    this.calculateBatchOverages(data.value['no_of_lots']);
    for (let i = 0; i < this.work_order_raw_materials.length; i++) {
      this.work_order_raw_materials[i]['lod_status'] =
        data.value['lod_criteria'] == 'As Such Basis' ? 'No' : 'Yes';
      this.work_order_raw_materials[i]['assay_status'] =
        data.value['assay_criteria'] == 'As Such Basis' ? 'No' : 'Yes';
    }
    this.segregate_materials();
    this.split_lots(data.value['no_of_lots']);
    this.is_data_generated = true;

    this.groupedMaterials = this.work_order_raw_materials.reduce(
      (group, material) => {
        const { stage } = material;
        group[stage] = group[stage] ?? [];
        group[stage].push(material);
        return group;
      },
      {}
    );
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
      } else {
        temp[i]['each_lot_qty'] = temp[i]['total_final_qty'];
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
          lot_no: j + 1,
          lots: this.coatd_work_order_lot_materials,
        };
        this.coatd_work_order_lots.push(dataObj);
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
          lot_no: j + 1,
          lots: this.work_order_lot_materials,
        };
        this.work_order_lots.push(dataObj);
        this.work_order_lot_materials = [];
      }
    }
  }

  calculateBatchOverages(no_of_lots) {
    for (let i = 0; i < this.work_order_raw_materials.length; i++) {
      let ovrages = parseFloat(
        (Number(this.work_order_raw_materials[i]['batch_qty']) *
          Number(this.overages_percent)) /
          100 +
          ''
      ).toFixed(2);
      this.work_order_raw_materials[i]['batch_overages'] = parseFloat(
        this.overages_percent + ''
      ).toFixed(2);
      this.work_order_raw_materials[i]['total_final_qty'] = parseFloat(
        Number(this.work_order_raw_materials[i]['batch_qty']) +
          Number(ovrages) +
          ''
      ).toFixed(2);
      this.work_order_raw_materials[i]['each_lot_qty'] = parseFloat(
        Number(this.work_order_raw_materials[i]['total_final_qty']) /
          Number(no_of_lots) +
          ''
      ).toFixed(2);
    }
    for (let i = 0; i < this.work_order_packing_materials.length; i++) {
      let ovrages = parseFloat(
        (Number(this.work_order_packing_materials[i]['batch_qty']) *
          Number(this.overages_percent)) /
          100 +
          ''
      ).toFixed(2);
      this.work_order_packing_materials[i]['batch_overages'] = parseFloat(
        this.overages_percent + ''
      ).toFixed(2);
      this.work_order_packing_materials[i]['total_final_qty'] = parseFloat(
        Number(this.work_order_packing_materials[i]['batch_qty']) +
          Number(ovrages) +
          ''
      ).toFixed(2);
    }
  }

  saveData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    // if (!this.is_data_generated) {
    //   alertify.error('Please Generate Lod, Assay and Batch Overages');
    //   return;
    // }
    let dataObj = {
      batch_plan_id: this.selectedResult['id'],
      selected_batch_index: this.selected_batch_index,
      ebmr_status: data.value['ebmr_status'],
      lod_criteria: data.value['lod_criteria'],
      assay_criteria: data.value['assay_criteria'],
      batch_overages: data.value['batch_overages'],
      overages_percent: data.value['overages_percent'],
      calculation_type: data.value['calculation_type'],
      no_of_lots: data.value['no_of_lots'],
      raw_materials: this.work_order_raw_materials,
      packing_materials: this.work_order_packing_materials,
      lots: this.work_order_lots,
      coated_lots: this.coatd_work_order_lots,
      common_materails: this.work_order_common_materials,
      material_type: this.selectedResult['material_type'],
    };
    this.service
      .post(
        'production/workorder.php?type=save_work_order',
        JSON.stringify(dataObj)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Work Order has been send to Approval');

          this.getPlans();
          this.get_batch_plan_details();
          this.is_prepare_work_order = false;
          this.is_view_batches = false;
          this.isView = true;
          offerNextStep(
            MEDICAP_PRODUCTION_FLOW.productionBatchPlanningApproval,
            'Production → Batch Planning Approval'
          );
        } else {
          alertify.error('Failed: ' + response['status']);
        }
      });
    console.log(JSON.stringify(dataObj));
  }
}
