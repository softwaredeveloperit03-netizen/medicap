import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bfrlog',
  templateUrl: './bfrlog.component.html',
  styleUrls: ['./bfrlog.component.css']
})
export class BfrlogComponent implements OnInit {
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
  /** Primary packing + consumables from unitformula row (API) */
  primary_pm_list: any[] = [];
  consumeableMaterial: any[] = [];
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
      .get('planning/raw.php?type=get_batch_formula_for_approval')
      .subscribe((response) => {
        this.results = response;
      });
  }
  getBatchFormaulsToBeApprove1(val) {
    let dsg = val;
    this.service
      .get(
        'planning/raw.php?type=get_batch_formula_for_approval1&dossageForm=' +
          dsg
      )
      .subscribe((response) => {
        this.results = response;
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

  groupedMaterials: { [key: string]: any[] } | any[] = {};

  private parseJsonArray(value: any): any[] {
    if (value == null || value === '') {
      return [];
    }
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string') {
      try {
        const p = JSON.parse(value);
        return Array.isArray(p) ? p : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  private parsePackingConfiguration(value: any): any[] {
    if (value == null || value === '') {
      return [];
    }
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string') {
      try {
        const p = JSON.parse(value);
        return Array.isArray(p) ? p : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  viewBfr(index) {
    this.groupedMaterials = {};

    this.selectedResult = this.results[index];
    const raw_materials = this.parseJsonArray(this.selectedResult['raw_materials']);
    this.bfr_list = this.selectedResult['bfr_records'] || [];
    this.primary_pm_list = this.parseJsonArray(this.selectedResult['primary_pm_list']);
    this.consumeableMaterial = this.parseJsonArray(this.selectedResult['consumeableMaterial']);
    this.packing_List_All = this.parsePackingConfiguration(
      this.selectedResult['packing_configuration']
    );
    this.isLog_view = true;
    this.isApproval_view = false;
    this.isApproval = false;
    this.isLog = false;

    this.groupedMaterials = raw_materials.reduce((group, material) => {
      const { stage } = material;
      const key = stage != null && stage !== '' ? stage : 'Default';
      group[key] = group[key] ?? [];
      group[key].push(material);
      return group;
    }, {});
  }
  viewBfrRecord(index) {
    this.selectedBfr = this.bfr_list[index];
    this.raw_materials = this.parseJsonArray(this.selectedBfr['raw_materials']);
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
    this.is_Bfr_view = true;
    this.isLog_view = false;
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
    // this.raw_materials = JSON.parse(this.selectedResult['raw_materials']);
    // this.packing_List_All = JSON.parse(this.selectedResult['packing_materials']);
    if (this.packing_List_All == null) {
      this.packing_List_All = [];
    }
    this.pack_size = [];
    for (let x = 0; x < this.packing_List_All.length; x++) {
      let obj = {
        id: this.packing_List_All[x]['id'],
        pack_size:
          this.packing_List_All[x]['pm_pack_size'] +
          '-' +
          this.packing_List_All[x]['pm_pack_unit'],
      };
      this.pack_size.push(obj);
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
    this.isApproval_view = true;
    this.isLog = false;
    this.isApproval = false;

    const raw_materials = this.selectedResult['raw_materials'];

    this.groupedMaterials = raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});
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
    this.primary_pm_list = [];
    this.consumeableMaterial = [];
    this.packing_List_All = [];
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
    let obj = {
      id: this.selectedResult['id'],
      status: status,
      bfr_no: this.selectedResult['bfr_no'],
    };
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
          this.getBatchFormaulsLog();
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
