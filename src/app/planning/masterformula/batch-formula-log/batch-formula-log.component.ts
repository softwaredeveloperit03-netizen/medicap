import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  MEDICAP_PRODUCTION_FLOW,
  offerNextStep,
  productionDirectPlanRoute,
} from 'src/app/shared/medicap-production-flow';
declare let alertify;
@Component({
  selector: 'app-batch-formula-log',
  templateUrl: './batch-formula-log.component.html',
  styleUrls: ['./batch-formula-log.component.css'],
})
export class BatchFormulaLogComponent implements OnInit {
  isApproval = true;
  isApproval_view = false;
  is_Bfr_view = false;
  isLog_view = false;
  isView = false;
  isLog = true;
  results;
  pm_selected_index = -1;
  pack_size = [];
  packingList = [];
  selectedResult = [];
  sub_types;
  bfr_list;
  selectedBfr;
  dosage_form = '';
  product_code = '';
  status = '';
  packing_List_All = [];
  raw_materials = [];
  materials;
  dosages;
  products;
  unit_batch_size = 0;
  selectedMaterial = [];
  plant_type: any;
  plant_id: any;
  productName;
  emp_id: string;
  isDIGI: boolean;

  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getSubMaterials();
    this.getBatchFormaulsLog();
    //this.getDosages();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.get_rights();
  }

  getBatchFormaulsToBeApprove() {
    this.isLog = false;
    this.isApproval = true;
    this.service
      .getJsonArray('planning/raw.php?type=get_batch_formula_for_approval&status=pending')
      .subscribe((response) => {
        this.results = response || [];
      });
  }
  getBatchFormaulsToBeApprove1(val) {
    let dsg = val;
    this.service
      .getJsonArray(
        'planning/raw.php?type=get_batch_formula_for_approval1&dsg=' +
          encodeURIComponent(dsg)
      )
      .subscribe((response) => {
        this.results = response || [];
      });
  }
  getBatchFormaulsLog() {
    this.isLog = true;
    this.isApproval = false;
    this.service
      .get('planning/raw.php?type=get_batch_formula_log')
      .subscribe((response) => {
        this.results = response;
      });
  }

  getBatchFormaulsLog1(val) {
    let productName = val;
    this.service
      .get(
        'planning/raw.php?type=get_batch_formula_log1&productName=' +
          productName
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe((response) => {
      this.dosages = response;
    });
  }

  getProducts() {
    this.service
      .get(
        'common.php?type=getProductsByDosage&dosage_form=' + this.dosage_form
      )
      .subscribe((response) => {
        this.products = response;
      });
  }

  groupedMaterials = [];

  /** Fill missing percent_qty from qty / average_weight (or batch size). */
  resolvePercentQty(material, averageWeight?) {
    if (!material) {
      return '';
    }
    const existing =
      material.percent_qty ?? material.percentage ?? material.percent ?? material['Qty(%)'];
    if (existing !== null && existing !== undefined && existing !== '' && !Number.isNaN(Number(existing))) {
      return existing;
    }
    const avg = Number(
      averageWeight ??
        this.selectedResult?.['average_weight'] ??
        this.selectedResult?.['batch_size'] ??
        this.selectedBfr?.['batch_formula_weight'] ??
        0
    );
    const qty = Number(
      material.qty_overages_qty ?? material.total_qty ?? material.qty ?? 0
    );
    if (avg > 0 && qty > 0) {
      return parseFloat(((qty / avg) * 100).toFixed(6));
    }
    return '';
  }

  normalizeRawMaterials(raw_materials, averageWeight?) {
    if (!Array.isArray(raw_materials)) {
      return [];
    }
    return raw_materials.map((material) => {
      const row = { ...material };
      row.percent_qty = this.resolvePercentQty(row, averageWeight);
      return row;
    });
  }

  groupMaterialsByStage(raw_materials, averageWeight?) {
    const materials = this.normalizeRawMaterials(raw_materials, averageWeight);
    return materials.reduce((group, material) => {
      const stage = material.stage || 'NA';
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});
  }

  viewBfr(index) {
    this.groupedMaterials = [];

    this.selectedResult = this.results[index];
    const raw_materials = this.selectedResult['raw_materials'];
    this.bfr_list = this.results[index]['bfr_records'];
    this.isLog_view = true;
    this.isApproval_view = false;
    // this.isApproval_view=true;
    this.isApproval = false;
    this.isLog = false;

    this.groupedMaterials = this.groupMaterialsByStage(
      raw_materials,
      this.selectedResult['average_weight'] || this.selectedResult['batch_size']
    );
  }
  viewBfrRecord(index) {
    this.selectedBfr = this.bfr_list[index];
    const parsed = typeof this.selectedBfr['raw_materials'] === 'string'
      ? JSON.parse(this.selectedBfr['raw_materials'])
      : this.selectedBfr['raw_materials'];
    this.raw_materials = this.normalizeRawMaterials(
      parsed,
      this.selectedBfr['batch_formula_weight'] || this.selectedResult?.['average_weight']
    );
    this.groupedMaterials = this.groupMaterialsByStage(
      this.raw_materials,
      this.selectedBfr['batch_formula_weight'] || this.selectedResult?.['average_weight']
    );

    this.packing_List_All = [];
    this.packingList = [];
    this.loadPackingForBfrView();

    this.is_Bfr_view = true;
    this.isLog_view = false;
  }

  /** True when list has at least one pack config with materials */
  hasPackingRows(list: any[]): boolean {
    if (!Array.isArray(list) || !list.length) {
      return false;
    }
    return list.some((p) => {
      const mats = p?.packing_materials || p?.packing_list || [];
      return Array.isArray(mats) && mats.length > 0;
    });
  }

  /**
   * Packing source order (same data unitformula log uses):
   * 1) BFR packing_materials JSON
   * 2) unitformula packing_configuration (from log API / by unit_formula id)
   * 3) batch_materials for this BFR
   */
  loadPackingForBfrView() {
    // 1) BFR JSON
    let list = this.parsePackingMaterials(this.selectedBfr?.['packing_materials']);
    if (this.hasPackingRows(list)) {
      this.setPackingList(list);
      return;
    }

    // 2) Already attached on selected unitformula row by get_batch_formula_log
    list = this.parsePackingMaterials(this.selectedResult?.['packing_configuration']);
    if (this.hasPackingRows(list)) {
      this.setPackingList(list);
      return;
    }

    // 3) Fetch packing_configuration by unitformula id / mfr
    const ufId = this.selectedResult?.['id'];
    if (ufId) {
      this.service
        .get(
          'planning/raw.php?type=get_packing_configuration_by_unitformula&unit_formula_id=' +
            encodeURIComponent(ufId)
        )
        .subscribe(
          (response: any) => {
            const fromUf = this.parsePackingMaterials(response);
            if (this.hasPackingRows(fromUf)) {
              this.setPackingList(fromUf);
              return;
            }
            this.loadPackingFromBatchMaterials(this.selectedBfr?.['bfr_no']);
          },
          () => this.loadPackingFromBatchMaterials(this.selectedBfr?.['bfr_no'])
        );
      return;
    }

    // 4) batch_materials fallback
    this.loadPackingFromBatchMaterials(this.selectedBfr?.['bfr_no']);
  }

  setPackingList(list: any[]) {
    this.packing_List_All = list;
    this.packingList = list;
  }

  /** Normalize packing_materials / packing_configuration into pack-config list used by the view */
  parsePackingMaterials(raw: any): any[] {
    if (raw == null || raw === '' || raw === 'null' || raw === '[]') {
      return [];
    }
    let data = raw;
    if (typeof raw === 'string') {
      try {
        data = JSON.parse(raw);
      } catch (e) {
        return [];
      }
    }
    if (!Array.isArray(data)) {
      return [];
    }
    // Flat list of materials (no pack config wrapper)
    if (data.length && data[0] && data[0].material_code && !data[0].packing_materials && !data[0].packing_list) {
      return [{
        country_name: '',
        packing_type: '',
        batch_size: this.selectedBfr?.['batch_formula_weight'] || '',
        pack_size: data[0].pack_size || data[0].pm_pack_size || '',
        unit: data[0].pack_unit || data[0].pm_pack_unit || data[0].unit || '',
        pm_batch_size: this.selectedBfr?.['batch_formula_weight'] || '',
        pm_pack_size: data[0].pack_size || data[0].pm_pack_size || '',
        pm_pack_unit: data[0].pack_unit || data[0].pm_pack_unit || data[0].unit || '',
        packing_materials: data,
        packing_list: data,
      }];
    }
    return data.map((item: any) => {
      const mats = item.packing_materials || item.packing_list || [];
      return {
        ...item,
        country_name: item.country_name || '',
        packing_type: item.packing_type || '',
        batch_size: item.batch_size || item.pm_batch_size || '',
        pack_size: item.pack_size || item.pm_pack_size || '',
        unit: item.unit || item.pm_pack_unit || item.pack_unit || '',
        pm_batch_size: item.pm_batch_size || item.batch_size || '',
        pm_pack_size: item.pm_pack_size || item.pack_size || '',
        pm_pack_unit: item.pm_pack_unit || item.unit || item.pack_unit || '',
        packing_materials: mats,
        packing_list: mats,
      };
    });
  }

  loadPackingFromBatchMaterials(bfrNo: string) {
    if (!bfrNo) {
      return;
    }
    this.service
      .get('planning/raw.php?type=get_packing_materials_grouped_by_bfr&bfr_no=' + encodeURIComponent(bfrNo))
      .subscribe((response: any) => {
        const list = this.parsePackingMaterials(response);
        if (this.hasPackingRows(list)) {
          this.setPackingList(list);
        }
      }, () => {
        // keep empty list
      });
  }
  closeBfrview() {
    this.is_Bfr_view = false;
    this.isLog_view = true;
  }
  // view(index) {
  //   this.selectedResult = this.results[index];
  //   this.unit_batch_size = Number(this.selectedResult['batch_formula_weight']);
  //   this.raw_materials = JSON.parse(this.selectedResult['raw_materials']);
  //   this.packing_List_All = JSON.parse(this.selectedResult['packing_materials']);
  //   if (this.packing_List_All == null) {
  //     this.packing_List_All = [];
  //   }
  //   this.pack_size=[];
  //   for (let x = 0; x < this.packing_List_All.length; x++) {
  //     let obj = {
  //       "id": this.packing_List_All[x]['id'],
  //       "pack_size": this.packing_List_All[x]['pm_pack_size'] + '-' + this.packing_List_All[x]['pm_pack_unit']
  //     }
  //     this.pack_size.push(obj);
  //   }
  //   for (let i = 0; i < this.raw_materials.length; i++) {
  //     let grade_name = '';
  //     let grade = this.raw_materials[i]['grade'];
  //     for (let j = 0; j < grade.length; j++) {
  //       grade_name = grade_name + grade[j]['grade'] + ' ';
  //     }
  //     grade_name = grade_name.trim().replace(' ', ',');
  //     this.raw_materials[i]['grade'] = grade_name;
  //   }
  //   this.isApproval_view = false;
  //   this.isLog = false;
  //   this.isApproval = true;
  // }
  view(index) {
    this.groupedMaterials = [];

    this.selectedResult = this.results[index];
    this.unit_batch_size = Number(this.selectedResult['batch_formula_weight']);
    this.packing_List_All = [];
    this.packingList = [];
    this.pm_selected_index = -1;
    this.pack_size = [];

    this.isApproval_view = true;
    this.isLog = false;
    this.isApproval = false;

    const raw_materials = this.selectedResult['raw_materials'];

    this.groupedMaterials = this.groupMaterialsByStage(
      raw_materials,
      this.selectedResult['average_weight'] ||
        this.selectedResult['batch_formula_weight'] ||
        this.selectedResult['batch_size']
    );

    // Approval view packing list (was hidden: pm_selected_index stayed -1)
    this.loadPackingForApprovalView();
  }

  /** Load packing for Approval screen (selectedResult is a BFR row) */
  loadPackingForApprovalView() {
    // 1) BFR packing_materials JSON
    let list = this.parsePackingMaterials(this.selectedResult?.['packing_materials']);
    if (this.hasPackingRows(list)) {
      this.applyApprovalPacking(list);
      return;
    }

    // 2) packing_configuration attached by approval API
    list = this.parsePackingMaterials(this.selectedResult?.['packing_configuration']);
    if (this.hasPackingRows(list)) {
      this.applyApprovalPacking(list);
      return;
    }

    // 3) unitformula packing by unit_formula_id / mfr_no
    const ufId = this.selectedResult?.['unit_formula_id'];
    const mfr = this.selectedResult?.['mfr_no'];
    if (ufId) {
      this.service
        .get(
          'planning/raw.php?type=get_packing_configuration_by_unitformula&unit_formula_id=' +
            encodeURIComponent(ufId)
        )
        .subscribe(
          (response: any) => {
            const fromUf = this.parsePackingMaterials(response);
            if (this.hasPackingRows(fromUf)) {
              this.applyApprovalPacking(fromUf);
              return;
            }
            this.loadPackingFromBatchMaterialsForApproval();
          },
          () => this.loadPackingFromBatchMaterialsForApproval()
        );
      return;
    }
    if (mfr) {
      this.service
        .get(
          'planning/raw.php?type=get_packing_configuration_by_mfr&mfr_no=' +
            encodeURIComponent(mfr)
        )
        .subscribe(
          (response: any) => {
            const fromUf = this.parsePackingMaterials(response);
            if (this.hasPackingRows(fromUf)) {
              this.applyApprovalPacking(fromUf);
              return;
            }
            this.loadPackingFromBatchMaterialsForApproval();
          },
          () => this.loadPackingFromBatchMaterialsForApproval()
        );
      return;
    }

    this.loadPackingFromBatchMaterialsForApproval();
  }

  loadPackingFromBatchMaterialsForApproval() {
    const bfrNo = this.selectedResult?.['bfr_no'];
    if (!bfrNo) {
      return;
    }
    this.service
      .get('planning/raw.php?type=get_packing_materials_grouped_by_bfr&bfr_no=' + encodeURIComponent(bfrNo))
      .subscribe((response: any) => {
        const list = this.parsePackingMaterials(response);
        if (this.hasPackingRows(list)) {
          this.applyApprovalPacking(list);
        }
      });
  }

  applyApprovalPacking(list: any[]) {
    this.setPackingList(list);
    this.pm_selected_index = 0;
    this.pack_size = list.map((p, idx) => ({
      id: p.id || idx + 1,
      pack_size: (p.pm_pack_size || p.pack_size || '') + '-' + (p.pm_pack_unit || p.unit || ''),
    }));

    // Fill batch_qty like getPackingMaterial()
    const batchSize = Number(this.unit_batch_size) || Number(this.selectedResult?.['batch_formula_weight']) || 0;
    for (let i = 0; i < this.packingList.length; i++) {
      const mats = this.packingList[i].packing_list || this.packingList[i].packing_materials || [];
      for (let x = 0; x < mats.length; x++) {
        const totalQty = Number(mats[x]['total_qty']);
        const pmBatch = Number(this.packingList[i]['pm_batch_size'] || this.packingList[i]['batch_size']);
        if (totalQty > 0 && pmBatch > 0 && batchSize > 0) {
          const batch_qty = (pmBatch / totalQty) * batchSize;
          mats[x]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
        }
      }
      this.packingList[i].packing_list = mats;
      this.packingList[i].packing_materials = mats;
    }
  }

  getPackingMaterial(idx) {
    this.packingList = [];
    this.packingList.push(this.packing_List_All[idx - 1]);
    this.pm_selected_index = idx - 1;
    // this.unit_batch_size
    for (let x = 0; x < this.packingList[0]['packing_list'].length; x++) {
      let batch_qty =
        (Number(this.packingList[0]['pm_batch_size']) /
          Number(this.packingList[0]['packing_list'][x]['total_qty'])) *
        this.unit_batch_size;
      this.packingList[0]['packing_list'][x]['batch_qty'] = parseFloat(
        batch_qty + ''
      ).toFixed(2);
    }
  }
  showHomePage() {
    this.isApproval = false;
    this.isApproval_view = false;
    this.is_Bfr_view = false;
    this.isLog_view = false;
    this.isView = false;
    this.isLog = true;
  }
  download() {
    this.service.open(
      'production/unitformula.php?type=downloadUnitFormla&id=' +
        this.selectedResult['id']
    );
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  getSubMaterials() {
    let value = 'Packing Material';
    let idx = 0;
    this.service.observableMaterialTypes.subscribe((response) => {
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_type'] == value) {
          idx = i;
        }
      }
      this.sub_types = [];
      // this.sub_types = response[idx]['sub_materials'];
    });
  }

  getMaterialsBySubType(value) {
    this.service
      .get(
        'common.php?type=getMaterialsByType&material_subtype=' +
          value +
          '&material_nature='
      )
      .subscribe((response) => {
        this.materials = response;
      });
  }
  getSelectedMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
      if ((this.selectedMaterial['unit'] = '')) {
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
  calculateBatchQty(batch_size) {
    for (let i = 0; i < this.raw_materials.length; i++) {
      let batch_qty =
        (Number(this.raw_materials[i]['total_qty']) /
          Number(this.selectedResult['batch_size'])) *
        Number(batch_size);
      this.raw_materials[i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(
        2
      );
    }
  }
  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }

    if (this.raw_materials.length == 0) {
      alertify.error('Raw Materials are required');
      return;
    }
    let temp = data.value;

    temp['raw_materials'] = this.raw_materials;
    temp['packing_materials'] = this.packing_List_All;
    temp['product_type'] = 'Raw Material';
    this.service
      .post('planning/raw.php?type=save_batch_formula', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success(
            'Product Batch Formula has been saved successfully!'
          );
          this.isView = false;
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
  updateStatus(status) {
    // let obj ={
    //   "id":this.selectedResult['id'],
    //   "status" : status,
    //   "packing_materials": this.packingList[0]['packing_list'],
    //   "pm_pack_size": this.packingList[0]['pm_pack_size'],
    //   "pm_pack_unit": this.packingList[0]['pm_pack_unit'],
    //   "bfr_no": this.selectedResult['bfr_no']
    // }
    let obj: any = {
      id: this.selectedResult['id'],
      status: status === 'Approve' ? 'Approve' : status,
      bfr_no: this.selectedResult['bfr_no'],
    };
    if (this.packingList && this.packingList.length > 0) {
      obj['packing_materials'] = this.packingList[0]['packing_list'] || this.packingList[0]['packing_materials'];
      obj['pm_pack_size'] = this.packingList[0]['pm_pack_size'] || this.packingList[0]['pack_size'];
      obj['pm_pack_unit'] = this.packingList[0]['pm_pack_unit'] || this.packingList[0]['unit'];
    }
    this.service
      .post(
        'planning/raw.php?type=update_batch_formula_status',
        JSON.stringify(obj)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Saved Successfully');
           this.isApproval_view = false;
          this.isApproval = true;
          this.isView = false;
          this.isLog = false;
          this.getBatchFormaulsToBeApprove();
          if (status === 'Approve') {
            offerNextStep(
              productionDirectPlanRoute(this.plant_type),
              'Planning → Production (Direct Plan)'
            );
          }
        } else {
          alertify.error(response['status']);
        }
      });
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
  openDigiSign(value) {
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status = value;
  }

  loginPassward = '';
  digiSign(data) {
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

    this.service
      .get(
        'login.php?type=checkDigiSIgn&mpin=' +
          this.loginPassward +
          '&emp_id=' +
          this.emp_id
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Digi-Sign Verified successfully');
          this.isDIGI = false;
          this.loginPassward = '';
          this.updateStatus(this.status);
        } else {
          alertify.error('Digi-Sign Not Verified');
        }
      });
  }
  isNew = false;
  handleClick() {
    this.isNew = false;
    this.isView = false;
  }


  isApprovalfUN() {
    this.isApproval_view = false;
    this.isApproval = true;
  }
}
