import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';

declare let alertify;

@Component({
  selector: 'app-lit-product-formulation',
  templateUrl: './lit-product-formulation.component.html',
  styleUrls: ['./lit-product-formulation.component.css'],
})
export class LitProductFormulationComponent implements OnInit {
  isView = false;
  isEdit = false;
  results;
  isMrpModel = false;

  selectedResult = [];
  styles;
  product_name = '';
  style = '';
  isStyle = false;
  selected = [];
  dosage_form = '';
  doseunits;
  grade = '';
  grades;
  type;
  isDelete = false;
  dosages;
  isMaterial = false;
  isDoseUnit = false;
  mrp;
  shelf_life;
  product_code;
  manufactured_for;
  manufactured_under;
  dosage_type = '';
  generic_name;
  packing_style;
  testing_time;
  retest;
  thera;
  storage_condition;
  apperance;
  tshape;
  tsize;
  hsn;
  isShelf = false;
  gtin;
  colors;
  fg_sub_materials = [];
  storages;
  isStorage = false;
  isColor = false;
  color = '';
  plant;
  fg_shapes = [];
  fg_sub_types = [];
  fg_sizes = [];
  shelf = '';
  short_code;
  dose_unit = '';
  shelfs;
  product_color = '';
  labels = [];
  // category = 'Branded';
  category = '';
  flag = 0;
  results1 = [];
  batch_type;
  label_claim;
  tertiary_packing;
  master_cartain;
  wad_sealing;
  shrink;
  scoop_add;
  artwork;
  mopcup;
  material_type: any;
  plant_id: any;
  packing_type: any;
  addpacking = false;
  add_primary_packing = false;
  add_secondary_packing = false;
  add_pack_size = false;
  add_no_pouch = false;
  add_capsule_size = false;
  add_mono_cartain = false;
  add_mono_qty = false;
  add_master_mono_qty = false;
  Addtertiary_packing = false;
  unit;
  similar_name;
  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  selectedFile6: File;
  artworkList = [];
  list = [];

  // searchQuery;
  productsList: any;
  isCategory = false;
  clientsList: any;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }
  ngOnInit(): void {
    this.getProducts();
    this.getDoseUnits();
    this.getProducts1();
    this.getprimary_packing_type();
    this.getadd_secondary_packing();
    this.getadd_pack_size();
    this.get_add_mono_cartains();
    this.get_capsule_size();
    this.get_add_mono_qtys();
    this.get_add_master_mono_qtys();
    this.get_fg_material();
    this.get_save_tertiary_packing();
    this.getClients('abc');
    this.getpacking_type();
    this.service.observableDosage.subscribe((response) => {
      this.dosages = response;
    });
    this.loadInitialApis();
    this.service.observableGrade.subscribe((response) => {
      this.grades = response;
    });
    this.get_Grade_fg();
    this.checkCopyFrom();
    this.getProducts1();
    this.get_rights();
  }

  private loadInitialApis() {
   
 
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

  selected_material_grade;
  grade1;
  get_grades;
  getEquivalent(index) {
    // index = index - 1;
    // if (index !== -1) {
    //   this.selected_material = this.active_materials[index];
    //   this.equivalancy_applicable = this.selected_material['equivalancy_applicable'];
    //   this.selectedEquivalent = this.selected_material['equivalancy_factor'];
    //   this.equivalancy_factor = this.selected_material['equivalancy_factor'];
    //   this.equivalent_to = this.selected_material['equivalent_to'];
    //   this.material_grade = this.selected_material['grade'];
    this.selected_material_grade = this.fg_material[index];

    this.service
      .get(
        'master/master.php?type=get_Grade&grade=' +
          this.selected_material_grade['grade']
      )
      .subscribe((response) => {
        this.get_grades = response;
        this.grade1 = this.get_grades[0].grade;
      });
    // }
  }
  fg_material;
  get_fg_material() {
    this.service
      .get('master/master.php?type=get_fg_material')
      .subscribe((response) => {
        this.fg_material = response;
      });
  }
  clients;
  getClients(value) {
    if (value !== 'Own') {
      this.service.get('common.php?type=getClients').subscribe((response) => {
        this.clients = response;
      });
    }
  }

  getClientsList(value) {
    if (value !== 'Own') {
      this.service.get('common.php?type=getClients').subscribe((response) => {
        this.clientsList = response;
      });
    } else {
      this.getProductsList('Own');
    }
  }
  // samplejjj() {
  //   this.isCategory = true;
  // }

  filterProduct() {
    this.results1 = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (
        material['category']
          .toUpperCase()
          .includes(this.category.toUpperCase()) &&
        material['grade'].toUpperCase().includes(this.grade.toUpperCase()) &&
        material['dosage_form']
          .toUpperCase()
          .includes(this.dosage_form.toUpperCase()) &&
        material['product_name']
          .toUpperCase()
          .includes(this.product_name.toUpperCase())
      ) {
        // if (material['category'].toUpperCase().includes(this.category.toUpperCase()) || material['grade'].toUpperCase().includes(this.grade.toUpperCase()) ) {
        this.results1[this.results1.length] = material;
      }
    }
  }

  save_Saipro(data) {
    let temp = data.value;
    console.log(temp);

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append('product_type', 'Formulation');
    if (this.selectedFile1 !== undefined) {
      uploadData.append(
        'product_lic',
        this.selectedFile1,
        this.selectedFile1.name
      );
    }

    uploadData.append('product_code', this.product_code);
    uploadData.append('id', this.id);
    // }
    console.log(uploadData);
    this.service
      .post(
        'master/product.php?type=editProduct&product_code=' +
          this.selectedResult['packing_type'],
        uploadData
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Product saved successfully');
          data.resetForm();

          // this.router.navigate(['/master/product/brand'])
          this.router.navigate(['/master']);
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  onFileChanged1(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile1 = event.target.files[0];
    }
  }

  onFileChanged2(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile2 = event.target.files[0];
    }
  }
  onFileChanged4(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile4 = event.target.files[0];
    }
  }
  onFileChanged5(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile5 = event.target.files[0];
    }
  }
  onFileChanged6(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile6 = event.target.files[0];
    }
  }

  deleteArtwork(index) {
    this.artworkList.splice(index, 1);
  }
  DeleteMocup(index) {
    this.list.splice(index, 1);
  }
  addMocupwork(data) {
    if (!data.valid) {
      alertify.error('All fiels are required');
      return;
    }
    let temp = data.value;
    this.list[this.list.length] = temp;
    data.reset();
  }

  getSizeAndShape(value) {
    let idx = 0;
    let data = null;
    this.fg_sizes = [];
    this.fg_shapes = [];
    this.fg_sub_types = [];
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

  getProducts() {
    this.service
      .get('master/product.php?type=getBrandProductsLogZuma')
      .subscribe((response) => {
        this.results = response;
        this.filterProduct();
        if (this.flag == 0) {
          this.getMaterialReqDetails();
          this.flag = 1;
        }
      });
  }

  onExcelUploaded() {
    this.getProducts();
  }
  //  getProducts() {
  //   this.service.get('master/product.php?type=getProducts_copy&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade).subscribe(response => {
  //     this.products = response;
  //   });
  // }

  // getGeneric() {
  //   this.service.get('master/product.php?type=getApprovedGenericProducts&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+ '&category=' + this.category).subscribe(response => {
  //     this.products = response;
  //   });
  // }

  // getLabelClaims() {
  //   this.service.get('master/product.php?type=getLabelClaims&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade).subscribe(response => {
  //     this.products = response;
  //   });
  // }
  primary_packing_type;
  saveprimary_packing_type() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=saveprimary_packing_type&primary_packing_type=' +
          this.primary_packing_type
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getprimary_packing_type();
          this.add_primary_packing = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  getprimary_packing_types;
  getprimary_packing_type() {
    this.service
      .get('master/color.php?type=getprimary_packing_type')
      .subscribe((response) => {
        this.getprimary_packing_types = response;
      });
  }
  add_sec_packing;
  saveadd_sec_packing() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=save_secondary_packings&save_secondary_packings=' +
          this.add_sec_packing
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getadd_secondary_packing();
          this.add_secondary_packing = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  getadd_secondary_packings;
  getadd_secondary_packing() {
    this.service
      .get('master/color.php?type=getsecondary_packings')
      .subscribe((response) => {
        this.getadd_secondary_packings = response;
      });
  }
  secondary_packing;
  addsecondary_packing(value) {
    if (value == 'Add New') {
      this.secondary_packing = '';
      this.add_secondary_packing = true;
    }
  }
  pack_size;
  addpack_size(value) {
    if (value == 'Add New') {
      this.pack_size = '';
      this.add_pack_size = true;
    }
  }
  add_packs_size;
  saveadd_packs_size() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=save_pack_size&add_pack_size=' +
          this.add_packs_size
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getadd_pack_size();
          this.add_pack_size = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  getadd_packs_sizes;
  getadd_pack_size() {
    this.service
      .get('master/color.php?type=getpack_size')
      .subscribe((response) => {
        this.getadd_packs_sizes = response;
      });
  }

  capsule_size;
  addcapsule_size(value) {
    if (value == 'Add New') {
      this.capsule_size = '';
      this.add_capsule_size = true;
    }
  }

  save_capsule_sizess;
  add_capsule_sizes() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=add_capsule_size&add_capsule_size=' +
          this.save_capsule_sizess
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.get_capsule_size();
          this.add_capsule_size = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  get_capsule_sizes;
  get_capsule_size() {
    this.service
      .get('master/color.php?type=get_capsule_size')
      .subscribe((response) => {
        this.get_capsule_sizes = response;
      });
  }

  mono_cartain;
  addmono_cartain(value) {
    if (value == 'Add New') {
      this.mono_cartain = '';
      this.add_mono_cartain = true;
    }
  }

  add_mono_cartains;
  saveadd_mono_cartains() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=add_mono_cartains&add_mono_cartains=' +
          this.add_mono_cartains
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.get_add_mono_cartains();
          this.add_mono_cartain = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  mono_cartains;
  get_add_mono_cartains() {
    this.service
      .get('master/color.php?type=get_add_mono_cartains')
      .subscribe((response) => {
        this.mono_cartains = response;
      });
  }

  mono_qty;
  addmono_qty(value) {
    if (value == 'Add New') {
      this.mono_qty = '';
      this.add_mono_qty = true;
    }
  }

  add_mono_qtys;
  saveadd_mono_qty() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=saveadd_mono_qty&add_mono_qtys=' +
          this.add_mono_qtys
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.get_add_mono_qtys();
          this.add_mono_qty = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  mono_qtys;
  get_add_mono_qtys() {
    this.service
      .get('master/color.php?type=get_add_mono_qtys')
      .subscribe((response) => {
        this.mono_qtys = response;
      });
  }

  master_mono_qty;
  addmaster_mono_qty(value) {
    if (value == 'Add New') {
      this.master_mono_qty = '';
      this.add_master_mono_qty = true;
    }
  }

  add_master_mono_qtys;
  saveadd_master_mono_qty() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=add_master_mono_qtys&add_master_mono_qtys=' +
          this.add_master_mono_qtys
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.get_add_master_mono_qtys();
          this.add_master_mono_qty = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }

  master_mono_qtyss;
  get_add_master_mono_qtys() {
    this.service
      .get('master/color.php?type=get_add_master_mono_qtys')
      .subscribe((response) => {
        this.master_mono_qtyss = response;
      });
  }

  getMaterialReqDetails() {
    // this.service.get('qa/material.php?type=getMaterialReqDetails').subscribe(response => {
    //   this.storages = response['storages'];
    //   this.shelfs = response['shelfs'];
    //   this.colors = response['colors'];
    //   this.styles = response['styles'];
    //   this.getMaterials();
    // });
    this.getColors();
    this.getShelf();
    this.getStorage();
    this.getStyles();
    this.getMaterials();
  }

  // getColors() {
  //   this.service.get('master/color.php?type=getColors1').subscribe(response => {
  //     this.colors = response;
  //   });
  // }
  getShelf() {
    this.service.get('qa/master.php?type=getShelfs').subscribe((response) => {
      this.shelfs = response;
    });
  }
  getStorage() {
    this.service
      .get('qa/master.php?type=getStorageConditions')
      .subscribe((response) => {
        this.storages = response;
      });
  }
  getStyles() {
    this.service
      .get('qa/master.php?type=getStyles')
      .subscribe((response: any) => {
        this.styles = response;
      });
  }
  newStorage(value) {
    if (value == 'ADD NEW') {
      this.storage_condition = '';
      this.isStorage = true;
    }
  }
  checkColor(value) {
    if (value == 'ADD NEW') {
      this.color = '';
      this.isColor = true;
    }
  }
  saveStorage() {
    if (this.storage_condition.length !== 0) {
      this.service
        .get(
          'qa/master.php?type=saveStorageCondition&storage_condition=' +
            this.storage_condition
        )
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getStorage();
            this.isStorage = false;
            this.storage_condition = '';
            alertify.success('Storage life saved successfully');
          } else {
            alertify.error('Failed: An error occured');
          }
        });
    }
  }
  download() {
    this.service.open('master/product.php?type=downloadBrandProductLog');
  }
  displayValue(key: string, fallback = 'NA'): string {
    if (!this.selectedResult) {
      return fallback;
    }
    const aliases: Record<string, string[]> = {
      Product_type: ['Product_type', 'product_type'],
      type: ['type', 'market_type'],
      Description: ['Description', 'other_description', 'pack_desc'],
      product_code: ['product_code', 'ProductCode'],
      generic_name: ['generic_name', 'similar_name'],
    };
    const keys = aliases[key] || [key];
    for (const k of keys) {
      const v = this.selectedResult[k];
      if (v !== null && v !== undefined && String(v).trim() !== '') {
        return String(v);
      }
    }
    return fallback;
  }

  isBrandProduct(): boolean {
    return this.displayValue('Product_type', '') === 'Brand';
  }

  getLabelClaims(): any[] {
    const raw = this.label_claim ?? this.selectedResult?.['label_claim'];
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string' && raw.trim()) {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  getLabelMeta(field: string): string {
    const claims = this.getLabelClaims();
    if (claims.length && claims[0][field] !== undefined && claims[0][field] !== null && String(claims[0][field]).trim() !== '') {
      return String(claims[0][field]);
    }
    const fromProduct = this.selectedResult?.[field];
    if (fromProduct !== undefined && fromProduct !== null && String(fromProduct).trim() !== '') {
      return String(fromProduct);
    }
    return 'NA';
  }

  getEachTitle(): string {
    const eachType = this.getLabelMeta('each_unit_type');
    if (eachType === 'Qty') {
      const qty = this.getLabelMeta('dose_unit_qty');
      const unit = this.getLabelMeta('dose_unit_qty_unit');
      return qty !== 'NA' && unit !== 'NA' ? `${qty} ${unit}` : '';
    }
    const doseType = this.getLabelMeta('dose_unit_type');
    return doseType !== 'NA' ? `${doseType} ` : '';
  }

  hasPrimarySubtype(): boolean {
    const subtype = this.displayValue('primary_subtype', '');
    return subtype !== 'NA' && subtype !== '';
  }

  printFormulationView(): void {
    window.print();
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.label_claim = this.normalizeLabelClaim(this.selectedResult['label_claim']);
    this.artwork = this.selectedResult['artwork'];
    this.mopcup = this.selectedResult['mopcup'];

    // Process the rest of the fields similarly
    this.selectedResult['packing_type'] =
      this.selectedResult['packing_type'] || 'NA';
    this.selectedResult['primary_packing'] =
      this.selectedResult['primary_packing'] || 'NA';
    this.selectedResult['secondary_packing'] =
      this.selectedResult['secondary_packing'] || 'NA';
    this.selectedResult['pack_sizes'] =
      this.selectedResult['pack_sizes'] || 'NA';
    this.selectedResult['nos_pouch'] = this.selectedResult['nos_pouch'] || 'NA';
    this.selectedResult['capsule_size'] =
      this.selectedResult['capsule_size'] || 'NA';
    this.selectedResult['mono_cartain'] =
      this.selectedResult['mono_cartain'] || 'NA';
    this.selectedResult['scoop_add'] = this.selectedResult['scoop_add'] || 'NA';
    this.selectedResult['Shrink'] = this.selectedResult['Shrink'] || 'NA';
    this.selectedResult['wad_sealing'] =
      this.selectedResult['wad_sealing'] || 'NA';
    this.selectedResult['mono_qty'] = this.selectedResult['mono_qty'] || 'NA';
    this.selectedResult['master_cartain'] =
      this.selectedResult['master_cartain'] || 'NA';
    this.selectedResult['master_mono_qty'] =
      this.selectedResult['master_mono_qty'] || 'NA';
    this.selectedResult['tertiary_packing'] =
      this.selectedResult['tertiary_packing'] || 'NA';
    this.selectedResult['tertiary_packing_data'] =
      this.selectedResult['tertiary_packing_data'] || 'NA';
    this.isView = true;
  }

  private normalizeLabelClaim(raw: any): any[] {
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string' && raw.trim()) {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  isMapBrand = false;

  map(index) {
    this.list = [];
    this.selectedResult = this.results[index];
    this.label_claim = this.selectedResult['label_claim'];
    this.artwork = this.selectedResult['artwork'];
    this.mopcup = this.selectedResult['mopcup'];
    this.list = this.selectedResult['brand_name'];

    // Process the rest of the fields similarly
    this.selectedResult['packing_type'] =
      this.selectedResult['packing_type'] || 'NA';
    this.selectedResult['primary_packing'] =
      this.selectedResult['primary_packing'] || 'NA';
    this.selectedResult['secondary_packing'] =
      this.selectedResult['secondary_packing'] || 'NA';
    this.selectedResult['pack_sizes'] =
      this.selectedResult['pack_sizes'] || 'NA';
    this.selectedResult['nos_pouch'] = this.selectedResult['nos_pouch'] || 'NA';
    this.selectedResult['capsule_size'] =
      this.selectedResult['capsule_size'] || 'NA';
    this.selectedResult['mono_cartain'] =
      this.selectedResult['mono_cartain'] || 'NA';
    this.selectedResult['scoop_add'] = this.selectedResult['scoop_add'] || 'NA';
    this.selectedResult['Shrink'] = this.selectedResult['Shrink'] || 'NA';
    this.selectedResult['wad_sealing'] =
      this.selectedResult['wad_sealing'] || 'NA';
    this.selectedResult['mono_qty'] = this.selectedResult['mono_qty'] || 'NA';
    this.selectedResult['master_cartain'] =
      this.selectedResult['master_cartain'] || 'NA';
    this.selectedResult['master_mono_qty'] =
      this.selectedResult['master_mono_qty'] || 'NA';
    this.selectedResult['tertiary_packing'] =
      this.selectedResult['tertiary_packing'] || 'NA';
    this.selectedResult['tertiary_packing_data'] =
      this.selectedResult['tertiary_packing_data'] || 'NA';
    this.isMapBrand = true;
    console.log(this.label_claim);
  }
  id;
  artwork_file;
  artwork_status;
  edit(index) {
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.packing_style = this.selectedResult['packing_style'];
    this.category = this.selectedResult['category'];
    this.shelf_life = this.selectedResult['shelf_life'];
    this.testing_time = this.selectedResult['testing_time'];
    // this.retest = this.selectedResult['retest'] + '';
    this.retest = this.selectedResult['retest'];
    this.thera = this.selectedResult['thera'];
    this.storage_condition = this.selectedResult['storage_condition'];
    this.apperance = this.selectedResult['apperance'];
    this.tshape = this.selectedResult['tshape'];
    this.dosage_type = this.selectedResult['dosage_type'];
    this.type = this.selectedResult['type'];
    this.copy_from = this.selectedResult['copy_from'];
    // this.dosage_form = this.selectedResult['dosage_form'];
    this.manufactured_under = this.selectedResult['manufactured_under'];
    this.plant = this.selectedResult['plant'];
    this.manufactured_for = this.selectedResult['manufactured_for'];
    this.batch_type = this.selectedResult['batch_type'];
    this.short_code = this.selectedResult['short_code'];
    this.grade = this.selectedResult['grade'];
    this.brand_generic = this.selectedResult['brand_generic'];
    this.product_name = this.selectedResult['product_name'];
    this.product_code = this.selectedResult['product_code'];
    this.generic_name = this.selectedResult['generic_name'];
    this.tsize = this.selectedResult['tsize'];
    this.apperance = this.selectedResult['label_claim']['apperance'];
    this.label_claim = this.selectedResult['label_claim'];
    this.mrp = this.selectedResult['mrp'];
    this.hsn = this.selectedResult['hsn'];
    this.packing_type = this.selectedResult['packing_type'];
    this.primary_packing = this.selectedResult['primary_packing'];
    this.secondary_packing = this.selectedResult['secondary_packing'];
    this.pack_size = this.selectedResult['pack_sizes'];
    this.no_pouch = this.selectedResult['no_pouch'];
    this.capsule_size = this.selectedResult['capsule_size'];
    this.mono_cartain = this.selectedResult['mono_cartain'];
    this.scoop_add = this.selectedResult['scoop_add'];
    this.shrink = this.selectedResult['Shrink'];
    this.tertiary_packing = this.selectedResult['tertiary_packing'];
    this.master_mono_qty = this.selectedResult['master_mono_qty'];
    this.master_cartain = this.selectedResult['master_cartain'];
    this.mono_qty = this.selectedResult['mono_qty'];
    this.wad_sealing = this.selectedResult['wad_sealing'];
    this.unit = this.selectedResult['unit'];
    this.similar_name = this.selectedResult['similar_name'];
    this.dosage_form = this.selectedResult['dosage_form'];
    this.id = this.selectedResult['id'];
    this.artworkList = this.selectedResult['artwork'];
    this.list = this.selectedResult['mopcup'];

    this.gtin = this.selectedResult['gtin'];
    // if (this.selectedResult['equivalents'] !== '') {
    //   this.labels = this.selectedResult['equivalents'];
    //   // console.log(this.labels);
    // }
    this.isEdit = true;
    console.log(this.selectedResult['label_claim']);
    console.log(this.artworkList);
    this.getOuter(this.selectedResult['isouter']);
    this.getSubtype(this.selectedResult['primary_packing']);
  }
  getColors() {
    this.service
      .get('master/color.php?type=getColors')
      .subscribe((response) => {
        this.colors = response;
      });
  }
  isShown = false;
  toggleShow() {
    this.isShown = !this.isShown;
  }

  savepacking_type() {
    // this.color = this.product_color;

    this.service
      .get('master/color.php?type=savePacking&pack_type=' + this.packing_type)
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getpacking_type();
          this.addpacking = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  getpacking_types;

  getpacking_type() {
    this.service
      .get('master/color.php?type=getpacking_type')
      .subscribe((response) => {
        this.getpacking_types = response;
      });
  }
  changePopUpMrp() {
    console.log('inside change Mrp');
    this.isMrpModel = true;
    console.log('inside change Mrp', this.isMrpModel);
  }
  primary_packing;
  addprimary_packing(value) {
    if (value == 'Add New') {
      this.primary_packing = '';
      this.add_primary_packing = true;
    }
  }

  no_pouch;
  addno_pouch(value) {
    if (value == 'Add New') {
      this.no_pouch = '';
      this.add_no_pouch = true;
    }
  }

  getFGMaterials(value) {
    this.service.observableFGTypes.subscribe((response) => {
      // let data =response;
      if (value == '') {
        return;
      }
      let idx = -1;
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_subtype'] == value) {
          idx = i;
        }
      }
      if (idx >= 0) {
        let data = response[idx];
        this.fg_sub_materials = data['sub_materials'];
        this.checkCopyFrom();
      } else {
        this.fg_sub_materials = [];
      }
    });
  }

  save_tertiary_packing;
  add_tertiary_packings() {
    // this.color = this.product_color;

    this.service
      .get(
        'master/color.php?type=save_tertiary_packing&save_tertiary_packing=' +
          this.save_tertiary_packing
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.get_save_tertiary_packing();
          this.Addtertiary_packing = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }
  get_tertiary_packing;
  get_save_tertiary_packing() {
    this.service
      .get('master/color.php?type=get_save_tertiary_packing')
      .subscribe((response) => {
        this.get_tertiary_packing = response;
      });
  }
  tertiary_packing_data;
  Add_tertiary_packing(value) {
    if (value == 'Add New') {
      this.tertiary_packing_data = '';
      this.Addtertiary_packing = true;
    }
  }

  getDetails(index, data) {
    index = index - 1;
    // if (index !== -1) {
    //   let product =this.products[index];
    //   for (let key in data.value) {
    //       data.value[key]= product[key];
    //   }

    this.selected = this.products1[index];
    this.labels = this.selected['label_claim'];
    this.shelf_life = this.selected['shelf_life'];
    this.packing_style = this.selected['packing_style'];
    this.storage_condition = this.selected['storage_condition'];
    this.color = this.selected['apperance'];
    // console.log( this.label_c);
    // }
  }
  products;

  addpackingType(value) {
    if (value == 'Add New') {
      this.packing_type = '';
      this.addpacking = true;
    }
  }

  getProductsList(client_code) {
    this.service
      .get('common.php?type=getProductsForChangeMrp&client_code=' + client_code)
      .subscribe((response) => {
        this.productsList = response;
      });
  }

  products1;
  // getProducts1() {
  //   this.service.get('master/product.php?type=getProducts_copy&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade).subscribe(response => {
  //     this.products1 = response;
  //   });
  // }
  // getGeneric() {
  //   this.service.get('master/product.php?type=getApprovedGenericProducts&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+ '&category=' + this.category).subscribe(response => {
  //     this.products1 = response;
  //   });
  // }

  // getLabelClaims() {
  //   this.service.get('master/product.php?type=getLabelClaims&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade).subscribe(response => {
  //     this.products1 = response;
  //   });
  // }

  checkCopyFrom() {
    if (this.brand_generic == 'Generic') {
      this.getGeneric();
    } else {
      if (this.copy_from == 'Similar Brand') {
        this.getProducts1();
      } else {
        this.getProducts1();
        // this.fetchLabelClaims();
      }
    }
  }

  getProducts1() {
    this.service
      .get(
        'master/product.php?type=getProducts_copy&dosage_type=' +
          this.dosage_type +
          '&dosage_form=' +
          this.dosage_form +
          '&grade=' +
          this.grade +
          '&brand_generic=' +
          this.brand_generic
      )
      .subscribe((response) => {
        this.products1 = response;
      });
  }

  getGeneric() {
    this.service
      .get(
        'master/product.php?type=getApprovedGenericProducts&dosage_type=' +
          this.dosage_type +
          '&dosage_form=' +
          this.dosage_form +
          '&grade=' +
          this.grade +
          '&category=' +
          this.category +
          '&brand_generic=' +
          this.brand_generic
      )
      .subscribe((response) => {
        this.products1 = response;
      });
  }

  fetchLabelClaims() {
    this.service
      .get(
        'master/product.php?type=getLabelClaims&dosage_type=' +
          this.dosage_type +
          '&dosage_form=' +
          this.dosage_form +
          '&grade=' +
          this.grade +
          '&brand_generic=' +
          this.brand_generic
      )
      .subscribe((response) => {
        this.products1 = response;
      });
  }
  get_gen_name() {
    this.service
      .get(
        'master/product.php?type=getApprovedGenericProducts&dosage_type=' +
          this.dosage_type +
          '&dosage_form=' +
          this.dosage_form +
          '&grade=' +
          this.grade +
          '&category=' +
          this.category +
          '&brand_generic=' +
          this.brand_generic
      )
      .subscribe((response) => {
        this.products1 = response;
      });
  }

  brand_generic: any;
  copy_from;

  material_grades;
  get_Grade_fg() {
    this.service
      .get('master/master.php?type=get_Grade_fg')
      .subscribe((response) => {
        this.material_grades = response;
      });
  }
  openlic(file) {
    if (file !== '') {
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  openfsc(file) {
    if (file !== '') {
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  opencopp(file) {
    if (file !== '') {
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  openphoto(file) {
    if (file !== '') {
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  checkStyle(value) {
    if (value == 'ADD NEW') {
      this.packing_style = '';
      this.isStyle = true;
    }
  }
  saveStyle() {
    this.packing_style = this.style;
    if (this.style.length !== 0) {
      this.service
        .get('qa/master.php?type=saveStyle&style=' + this.style)
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getStyles();
            alertify.success('Packing Style Saved Successfully!');
            this.isStyle = false;
            this.style = '';
            this.packing_style = '';
          } else {
            alertify.error('Failed: An error occured, please try again!');
          }
        });
    }
  }
  close(value) {
    if (value == 'style') {
      this.isStyle = false;
    }
  }
  close1() {
    this.isDelete = false;
  }
  saveShelf() {
    this.shelf_life = this.shelf;
    if (this.shelf.length !== 0) {
      this.service
        .get('qa/master.php?type=saveShelf&shelf=' + this.shelf)
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getShelf();
            this.isShelf = false;
            this.shelf = '';
            this.shelf_life = '';
            alertify.success('Shelf life saved successfully');
          } else {
            alertify.error('Failed: An error occured');
          }
        });
    }
  }
  newShelf(value) {
    if (value == 'ADD NEW') {
      this.shelf_life = '';
      this.isShelf = true;
    }
  }

  editMaterial(index) {
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.packing_style = this.selectedResult['packing_style'];
    this.shelf_life = this.selectedResult['shelf_life'];
    this.testing_time = this.selectedResult['testing_time'];
    // this.retest = this.selectedResult['retest'] + '';
    this.retest = this.selectedResult['retest'];
    this.thera = this.selectedResult['thera'];
    this.storage_condition = this.selectedResult['storage_condition'];
    this.apperance = this.selectedResult['apperance'];
    this.tshape = this.selectedResult['tshape'];
    this.tsize = this.selectedResult['tsize'];
    this.mrp = this.selectedResult['mrp'];
    this.hsn = this.selectedResult['hsn'];
    this.gtin = this.selectedResult['gtin'];
    if (this.selectedResult['equivalents'] !== '') {
      this.labels = this.selectedResult['equivalents'];
      // console.log(this.labels);
    }
    this.isMaterial = true;
  }

  addUpdate(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.selectedResult['packing_style'] = this.packing_style;
    this.selectedResult['shelf_life'] = this.shelf_life;
    this.selectedResult['testing_time'] = this.testing_time;
    // this.selectedResult['retest'] = this.retest;
    this.selectedResult['retest'] = this.retest;
    this.selectedResult['thera'] = this.thera;
    this.selectedResult['storage_condition'] = this.storage_condition;
    this.selectedResult['apperance'] = this.apperance;
    this.selectedResult['tshape'] = this.tshape;
    this.selectedResult['tsize'] = this.tsize;
    this.selectedResult['mrp'] = this.mrp;
    this.selectedResult['hsn'] = this.hsn;
    this.selectedResult['gtin'] = this.gtin;
    this.selectedResult['equivalents'] = this.labels;
    this.service
      .post(
        'master/product.php?type=editProduct&id=' + this.selectedResult['id'],
        JSON.stringify(this.selectedResult)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isMaterial = false;
          alertify.success('Record Updated  Successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
  del(index) {
    this.isDelete = true;
    this.selectedResult = this.results[index];
    this.product_code = this.selectedResult['product_code'];
    this.dosage_form = this.selectedResult['dosage_form'];
    this.product_name = this.selectedResult['product_name'];
    this.grade = this.selectedResult['grade'];
    this.mrp = this.selectedResult['mrp'];
    this.shelf_life = this.selectedResult['shelf_life'];
  }
  delete(index) {
    this.service
      .get(
        'master/product.php?type=deleteProduct&product_code=' +
          this.selectedResult['product_code']
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.isDelete = false;
          this.getProducts();
          alertify.success('Record Delete Successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  addArtwork(data) {
    if (!data.valid) {
      alertify.error('All fiels are required');
      return;
    }
    let temp = data.value;
    this.artworkList[this.artworkList.length] = temp;
    data.reset();
  }

  materials: any = [];
  getMaterials() {
    this.service
      .get('common.php?type=getMaterialsByType&material_subtype=API')
      .subscribe((response) => {
        this.materials = response;
      });
  }

  addLabel(data) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    this.label_claim[this.label_claim.length] = data.value;
    data.resetForm();
  }

  delLabel(index) {
    this.label_claim.splice(index, 1);
  }

  clearFilter() {
    this.category = '';
    this.product_name = '';
    this.grade = '';
    this.dosage_form = '';
    this.results1 = this.results;
  }
  dose_unit_type = '';
  dose_unit_qty_unit = '';
  dose_unit_qty = '';
  ech_title = '';

  checkDoseUnit(value) {
    if (value == 'ADD NEW') {
      this.dose_unit = '';
      this.isDoseUnit = true;
    } else {
      this.dose_unit_type = value;
      this.dose_unit_qty = '';
      this.dose_unit_qty_unit = '';
      this.ech_title = this.dose_unit_type + ' ';
    }
  }

  saveDoseUnit() {
    if (this.dose_unit.length !== 0) {
      this.service
        .get(
          'master/doseunit.php?type=saveDoseUnit1&dose_unit=' + this.dose_unit
        )
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getDoseUnits();
            this.isDoseUnit = false;
            this.dose_unit = '';
            alertify.success('Dose Unit aved successfully');
          } else {
            alertify.error(response['status']);
          }
        });
    } else {
      alertify.error('Enter Dose Unit');
    }
  }
  saveColor() {
    this.color = this.product_color;

    if (this.color.length !== 0) {
      this.service
        .get('master/color.php?type=saveColor&color=' + this.product_color)
        .subscribe((response) => {
          if (response['status'] == 'success') {
            this.getColors();
            this.isColor = false;
            this.product_color = '';
            this.color = '';
            alertify.success('Product Color saved successfully');
          } else {
            alertify.error(response['status']);
          }
        });
    }
  }
  getDoseUnits() {
    this.service
      .get('master/doseunit.php?type=get_dose_units1')
      .subscribe((response) => {
        this.doseunits = response;
      });
  }

  viewArt(url) {
    window.open(
      this.service.url +
        '../../upload/product/' +
        this.selectedResult['artwork_file']
    );

    window.open(url, '_blank');
  }
  viewShade(url) {
    window.open(
      this.service.url +
        '../../upload/product/' +
        this.selectedResult['mopcup_file']
    );

    window.open(url, '_blank');
  }

  // get filteredMaterials(): any[] {
  //   if (!this.searchQuery || this.searchQuery.trim() === '') {
  //     return this.results1; // If search query is empty or whitespace, return all materials
  //   }

  //   const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

  //   return this.results1.filter((material) => {
  //     // Check if any field of the material contains the search query
  //     return Object.entries(material).some(([key, value]) => {
  //       if (key === 'entry_date') {
  //         // Convert the value to a Date object if it's not already
  //         const dateValue = typeof value === 'string' ? new Date(value) : value;
  //         // Check if the date value is valid and includes the search query
  //         return (
  //           dateValue instanceof Date &&
  //           dateValue.toISOString().slice(0, 10).includes(query)
  //         );
  //       } else {
  //         // Convert field value to lowercase and check if it includes the search query
  //         return value && value.toString().toLowerCase().includes(query);
  //       }
  //     });
  //   });
  // }

  searchQuery: string = '';

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results1; // Use the pre-filtered list if `filterProduct()` is applied
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results1.filter((material) =>
      Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value && value.toString().toLowerCase().includes(query);
      })
    );
  }

  getHighlightedParts(
    value: string | number
  ): { text: string; match: boolean }[] {
    const text = value?.toString() || '';
    const query = this.searchQuery?.trim().toLowerCase();
    if (!query) return [{ text, match: false }];

    const lowerText = text.toLowerCase();
    const parts = [];
    let index = 0;

    while (index < text.length) {
      const matchIndex = lowerText.indexOf(query, index);
      if (matchIndex === -1) {
        parts.push({ text: text.slice(index), match: false });
        break;
      }

      if (matchIndex > index) {
        parts.push({ text: text.slice(index, matchIndex), match: false });
      }

      parts.push({
        text: text.slice(matchIndex, matchIndex + query.length),
        match: true,
      });
      index = matchIndex + query.length;
    }

    return parts;
  }

  saveMrp(data) {
    this.service
      .post('master/mrp.php?type=saveMrp', JSON.stringify(data.value))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.reset();
          this.isMrpModel = false;
          alertify.success(' saved successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
  saveBrand() {
    let temp = {};
    temp['brand_name_list'] = this.list;
    temp['product_code'] = this.selectedResult['product_code'];
    this.service
      .post('master/product.php?type=saveBrand', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.isMrpModel = false;
          alertify.success(' saved successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  isBlister = false;
  isOther = false;
  isBottel = false;
  isStrip = false;

  getSubtype(data) {
    if (data == 'Blister') {
      this.isBlister = true;
      this.isOther = false;
      this.isBottel = false;
      this.isStrip = false;
    } else if (data == 'Bottle') {
      this.isBlister = false;
      this.isOther = false;
      this.isBottel = true;
      this.isStrip = false;
    } else if (data == 'Strip') {
      this.isStrip = true;
      this.isBlister = false;
      this.isOther = false;
      this.isBottel = false;
    } else if (data == 'Other') {
      this.isOther = true;
      this.isStrip = false;
      this.isBlister = false;
      this.isBottel = false;
    }
  }

  isyes = false;
  isNo = false;
  getMono(data) {
    if (data == 'yes') {
      this.isyes = true;
      this.isNo = false;
    } else if (data == 'no') {
      this.isyes = false;
      this.isNo = true;
    }
  }

  isyesouter = false;
  isNoouter = false;
  getOuter(show) {
    if (show == 'yes') {
      this.isyesouter = true;
      this.isNoouter = false;
    } else if (show == 'no') {
      this.isyesouter = false;
      this.isNoouter = true;
    }
  }
  exportToExcelProductList(): void {
    const dataToExport = this.filteredMaterials.map((result, index) => ({
      'Sr. No.': index + 1,
      'Product Code': result.product_code || 'NA',
      'Product Name': result.product_name || 'NA',
      'Nature of Dose': result.dosage_type || 'NA',
      'Dosage Form': result.dosage_form || 'NA',
      'Entry Date': this.formatDate(result.entry_date),
      'Entry By': result.entry_by || 'NA',
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'ProductList');
    XLSX.writeFile(workbook, 'product_list.xlsx');
  }

  // Optional helper to format date
  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB'); // 'dd/MM/yyyy'
  }
}
