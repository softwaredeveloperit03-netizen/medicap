import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new-product-formulation',
  templateUrl: './new-product-formulation.component.html',
  styleUrls: ['./new-product-formulation.component.css']
})
export class NewProductFormulationComponent implements OnInit {


  Product_type = 'Generic Product';

 

  short_code1;
short_code2;
short_code3;
short_code4;
short_code5;
short_code6;
short_code7;
  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;
  selectedFile5: File;
  selectedFile6: File;
  ech_title = '';
  grades;
  qty = 0;
  strength = 0;
  factor;
  clients1;
  dosages;
  clients;
  generics;
  selected;
  isProduct = false;
  gstList;
  add_primary_packing=false;
  add_secondary_packing=false;
  add_pack_size=false;
  add_no_pouch=false;
  add_capsule_size=false;
  add_mono_cartain=false;
  add_mono_qty=false;
  add_master_mono_qty=false;
  Addtertiary_packing=false;
  isShown = false;
  isShown1 = false;
  productList = [];

  styles = [];
  isStyle = false;
  packing_style = '';
  style = '';
  show_view = false;
  equivalancy_applicable = 'No';
  equalant_to = '';
  selected_material = [];
  selectedResult = [];
  dose_unit_name = '';
  dose_unit_qty = '';
  dose_unit_type = '';
  apperance = '';
  dose_unit_name_txt = '';
  dose_unit_qty_unit = '';
  // category = 'Branded';
  copy_from = 'Similar Generic';
  products;
  product_each_units;
  dosage_type = '';
  category = '';
  dosage_form = '';
  grade = '';
  manufacture_under = 'Own';

  shelf_life = '';
  isShelf = false;
  shelf = '';
  shelfs;
  clientName2 ;
  storage_condition = '';
  selectedEquivalent = [];
  color = '';
  colors;
  doseunits;
  isColor = false;
  isDoseUnit = false;
  dose_unit = '';
  product_color = '';
  each_unit_type = '';
  labels = [];
  storages;
  isStorage = false;
  mrps;
  isMrp = false;
  product_group;
  market_group;
  materials;
  isProductgroup = false;
  isMarketgroup = false;
  productgroups;
  mastergroups;
  isBlister = false;
  isOther = false;
  isBottel = false;
  isStrip = false;
  isyes = false;
  isNo = false;
  isyesouter = false;
  isNoouter = false;
  addpacking = false;
  shapes;
  artworkList = [];
  list = [];
  isCashsales: boolean;
  cash: string;
  units;
  fg_material_list = [];
  fg_shapes = [];
  fg_sizes = [];
  active_materials;
  active_material;
  fg_sub_types = [];
  fg_sub_materials = []
  mrp_list = [];
  sale_price_list = [];
  change_part_items = [];
  punch_tools;
  change_part;
  equivalancy_factor = 0;
  equivalent_to = '';
  material_grade = ''
material_type: any;
 fssai_number: any;
dosage_sub_type: any;
other_product_description: any;
hsn: any;
gst: any;
fg_material:any;
clicked = false;
material_grades: any;
  label_c=[];
  plant_id:any;

  constructor(private service: DataAccessService, private router: Router) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
   }

  private toArray(response: any): any[] {
    return Array.isArray(response) ? response : [];
  }

  private safeJsonArray(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value !== 'string' || value.trim() === '') {
      return [];
    }
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  ngOnInit() {
 
      this.initSelectedProduct();
      this.getUnits();
    
      //this.getProducts();
      this.getShelf();
      this.getMarketgroup();
      this.getProductgroup();
      this.getShape();
 
      this.getStyles();
      this.getGst();
      this.getActiveMaterials();
      this.getStorageConditions();
      this.get_fg_material();
      this.get_Grade_fg();
      this. getpacking_type();
      this. getprimary_packing_type();
      this. getadd_secondary_packing();
      this. getadd_pack_size();
      this. get_capsule_size();
      this. getadd_nos_pouch();
      this. get_add_master_mono_qtys();
      this. get_add_mono_qtys();
      this. get_add_mono_cartains();
      this. get_save_tertiary_packing();

 
    this.getPunchTools();
    this.getChangePart();
    // this.service.observableGrade.subscribe(response => {
      // this.grades = response;
      this.getColors();
      this.getMaterials();
      this.getGrades();
      this.getApprovedGenericProductsList();
      this.checkCopyFrom();
      this.getClients1();
      this.getMrp();
      this.getDoseUnits();
      this.getSorageConditions();
    // });

    // Keep a safe default without relying on missing service observables.
    this.dosage_form = 'TABLET';
  }





  storage_conditions;

  getSorageConditions() {
     this.service.get('common.php?type=getStorageConditions').subscribe(response => {
      this.storage_conditions = response
    })
  }




  initSelectedProduct() {
    this.selected = {
      "id": "78",
      "user_no": "",
      "plant_id": "",
      "product_code": "",
      "product_type": "",
      "product_name": "",
      "generic_name": "",
      "grade": "",
      "packing_style": "",
      "testing_time": "0.00",
      "retest_period": "0.00",
      "hsn": "",
      "gst": "0.00",
      "category": "",
      "pack_desc": "",
      "tsize": "",
      "storage_condition": "",
      "market": "",
      "mrp": "",
      "manufactured_under": "",
      "manufactured_for": "",
      "product_lic": "",
      "fsc": "",
      "copp": "",
      "photo": "",
      "artwork": "",
      "artwork_status": "",
      "manufactured_type": "",
      "approve_by": "",
      "approve_date": null,
      "unit": "Kg",
      "rate": "0.00",
      "similar_name": "",
      "tshape": "",
      "apperance": "",
      "division": "",
      "product_apperance": "",
      "micro": "",
      "type": "",
      "product_nature": "",
      "dosage_type": "",
      "dosage_form": "",
      "packing_mode": "",
      "label_claim": "",
      "shelf_life": "",
      "min_shelf": "",
      "thera": "",
      "gtin": "",
      "mfg_lic": "",
      "plant": "In House",
      "sale_type": "",
      "expiry": "",
      "product_group": "",
      "market_group": "",
      "asin_no": "",
      "nrv_value": "",
      "sku_no": "",
      "packing_charges": "",
      "retain_qty": "",
      "retest": "",
      "min_qty": "",
      "mfg_charges": "",
      "max_qty": "",
      "configuration": "",
      "primary_packing": "",
      "primary_subtype": "",
      "ismono": "yes",
      "primary_qty": "",
      "unit_wt": "",
      "mono_qty": "",
      "outer_qty": "",
      "shipper_qty": "",
      "isouter": "no"
    }
    this.show_view = true;

  }
  getParts(idx) {
    this.change_part_items = [];
    const part = this.change_part?.[idx - 1];
    this.change_part_items = this.safeJsonArray(part?.['change_parts']);
  }

  get_fg_material() {

    this.service.get('master/master.php?type=get_fg_material').subscribe(response => {
      this.fg_material = response;

    });
  }

   getEquivalentTo(index){
     const sele = this.selectedEquivalent?.[index - 1];
     this.equivalancy_factor = Number(sele?.['equivalancy_factor'] || 0);
   }

  get_Grade_fg() {

    this.service.get('master/master.php?type=get_Grade_fg').subscribe(response => {
      this.material_grades = response;

    });
  }
  getActiveMaterials() {
    this.service.get('master/material.php?type=getActiveMaterials').subscribe(response => {
      this.active_materials = response;
    });
  }
  // getFGMaterials(value) {
  //   this.service.observableFGTypes.subscribe(response => {

  //     if (value == "") {
  //       return;
  //     }
  //     let idx = -1;
  //     for (let i = 0; i < response.length; i++) {
  //       if (response[i]['material_subtype'] == value) {
  //         idx = i;
  //       }
  //     }
  //     if (idx >= 0) {
  //       let data = response[idx]
  //       this.fg_sub_materials = data['sub_materials'];
  //       this.checkCopyFrom();
  //     } else {
  //       this.fg_sub_materials = []
  //     }
  //   });
  // }
  getDosageByNature(value) {
    this.service.get('master/materialtype.php?type=getDosageByNature&product_nature='+value).subscribe(response => {
      this.fg_sub_materials = this.toArray(response?.['data']);
      this.checkCopyFrom();
    });
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
      this.fg_sizes = this.safeJsonArray(data['dosage_sizes']);
      this.fg_shapes = this.safeJsonArray(data['dosage_shapes']);
      this.fg_sub_types = this.safeJsonArray(data['dosage_sub_form']);
    }
  }
  getColors() {
    this.service.get('master/color.php?type=getColors').subscribe(response => {
      this.colors = response;
    });
  }
  getDoseUnits() {
    this.service.get('master/doseunit.php?type=get_dose_units').subscribe(response => {
      this.doseunits = response;
    });
  }
  getUnits() {
    this.service.get('common.php?type=Getunit').subscribe(response => {
      this.units = response;
    });
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }
  selected_material_grade;
  grade1;
  selected_grade:any
  getEquivalent(index) {

    index = index -1;
    if (index < 0 || !Array.isArray(this.fg_material) || !this.fg_material[index]) {
      return;
    }

      this.selected_material_grade = this.fg_material[index-1];
      this.grade1 = this.selected_material_grade['grade'];


      if(this.selected_material_grade['equivalancy_applicable']  == 'Yes'){
        this.equivalancy_applicable = 'Yes';
         this.selectedEquivalent = this.selected_material_grade['equivalent_to'];
      }

      //   this.service.get('master/master.php?type=get_Grade&grade='+this.selected_material_grade['grade']).subscribe(response => {
      //   this.get_grades = this.toArray(response);
      //   this.grade1 = this.get_grades[0]?.grade || '';
      // });
    // }
  }
  get_grades;
  getGrade(){


      // this.service.get('master/master.php?type=get_Grade&grade='+this.fg_material['grade']).subscribe(response => {
      //   this.get_grades = response;
      // });


  }



  toggleShow() {
    this.isShown = !this.isShown;
  }
  toggleShow1() {
    this.isShown1 = !this.isShown1;
  }

  addArtwork(data) {
    if (!data.valid) {
      alertify.error("All fiels are required");
      return;
    }
    let temp = data.value;
    this.artworkList[this.artworkList.length] = temp;
    data.reset();
  }
  addMocupwork(data) {
    if (!data.valid) {
      alertify.error("All fiels are required");
      return;
    }
    let temp = data.value;
    this.list[this.list.length] = temp;
    data.reset();
  }

  deleteArtwork(index) {
    this.artworkList.splice(index, 1);
  }
  DeleteMocup(index) {
    this.list.splice(index, 1);
  }

  addProductgroup() {
    if (this.product_group == 'Add New') {
      this.isProductgroup = true;
    } else {
      this.isProductgroup = false;
    }
  }

  saveProductgroup(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['mrp_list'] = this.mrp_list;
    temp['selling_price_list'] = this.sale_price_list;
    this.service.post('master/productgroup.php?type=saveProductgroup', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getProductgroup();
        this.isProductgroup = false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  getStorageConditions() {
    this.service.get('qa/master.php?type=getStorageConditions').subscribe(response => {
      this.storages = response;
    })
  }
  getProductgroup() {
    this.service.get('master/productgroup.php?type=getProductgroup').subscribe(response => {
      this.productgroups = response;
    })
  }

  getShape() {
    this.service.get('master/shapemaster.php?type=getShape').subscribe(response => {
      this.shapes = response;
    })
  }

  getPunchTools() {
    this.service.get('master/tools.php?type=getTools').subscribe(response => {
      this.punch_tools = response;
    })
  }

  getChangePart() {
    this.change_part_items = [];
    this.service.get('master/blister.php?type=getChangeParts&token').subscribe(response => {
      this.change_part = response;
    })
  }

  addMarketgroup() {
    if (this.market_group == 'Add New') {
      this.isMarketgroup = true;
    } else {
      this.isMarketgroup = false;
    }
  }
  // addCashsales(){
  //   if(this.cash=='Add New'){
  //     this.isCashsales=true;
  //   }else{
  //     this.isCashsales=false;
  //   }
  // }
  saveMarketgroup(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    this.service.post('master/marketgroup.php?type=saveMarketgroup', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getMarketgroup();
        this.isMarketgroup = false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  getMarketgroup() {
    this.service.get('master/marketgroup.php?type=getMarketgroup').subscribe(response => {
      this.mastergroups = response;
    })
  }




  getMaterials() {
    this.service.get('common.php?type=getMaterialsByType&material_subtype=API').subscribe(response => {
      this.materials = response;
    });
  }

brand_generic = 'Generic';

  checkCopyFrom() {
    if (this.brand_generic == 'Generic') {
      this.getGeneric();
    } else {
      if (this.copy_from == 'Similar Brand') {
        this.getProducts();
      } else {
        this.getProducts();
        // this.getLabelClaims();
      }
    }
  }



  getProducts() {
    this.service.get('master/product.php?type=getProducts_copy&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = this.toArray(response);
    });
  }

  getGeneric() {
    this.service.get('master/product.php?type=getApprovedGenericProducts&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+ '&category=' + this.category+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = this.toArray(response);
    });
  }
  Gen_products;
  getApprovedGenericProductsList() {
    this.service.get('master/product.php?type=getApprovedGenericProductsList').subscribe(response => {
      this.Gen_products = this.toArray(response);
    });
  }

  getLabelClaims() {
    this.service.get('master/product.php?type=getLabelClaims&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = this.toArray(response);
    });
  }
  get_gen_name() {
    this.service.get('master/product.php?type=getApprovedGenericProducts&dosage_type=' + this.dosage_type + '&dosage_form=' + this.dosage_form + '&grade=' + this.grade+ '&category=' + this.category+'&brand_generic='+this.brand_generic).subscribe(response => {
      this.products = this.toArray(response);
    });
  }
  ismaterial_code(value) {

    this.service.get('master/product.php?type=check_material_code&material_code='+value).subscribe(response => {
      if (response['status'] == 'already exists') {
        alertify.error('Code Already Exists');
      }
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

  addpackingType(value) {
    if (value == 'Add New') {
      this.packing_type = '';
      this.addpacking = true;
    }
  }
  primary_packing;
  addprimary_packing(value) {
    if (value == 'Add New') {
      this.primary_packing = '';
      this.add_primary_packing = true;
    }
  }
  secondary_packing;
  addsecondary_packing(value) {
    if (value == 'Add New') {
      this.secondary_packing = '';
      this.add_secondary_packing = true;
    }
  }pack_size;
  addpack_size(value) {
    if (value == 'Add New') {
      this.pack_size = '';
      this.add_pack_size = true;
    }
  }no_pouch;
  addno_pouch(value) {
    if (value == 'Add New') {
      this.no_pouch = '';
      this.add_no_pouch = true;
    }
  }capsule_size;
  addcapsule_size(value) {
    if (value == 'Add New') {
      this.capsule_size = '';
      this.add_capsule_size = true;
    }
  }mono_cartain;
  addmono_cartain(value) {
    if (value == 'Add New') {
      this.mono_cartain = '';
      this.add_mono_cartain = true;
    }
  }mono_qty;
  addmono_qty(value) {
    if (value == 'Add New') {
      this.mono_qty = '';
      this.add_mono_qty = true;
    }
  }master_mono_qty;
  addmaster_mono_qty(value) {
    if (value == 'Add New') {
      this.master_mono_qty = '';
      this.add_master_mono_qty = true;
    }
  }tertiary_packing_data
  Add_tertiary_packing(value) {
    if (value == 'Add New') {
      this.tertiary_packing_data = '';
      this.Addtertiary_packing = true;
    }
  }







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

  saveStorage() {
    if (this.storage_condition.length !== 0) {
      this.service.get('qa/master.php?type=saveStorageCondition&storage_condition=' + this.storage_condition).subscribe(response => {
        if (response['status'] == 'success') {
          this.service.getStorages();
          this.isStorage = false;
          this.storage_condition = '';
          alertify.success('Storage Condition saved successfully');
        } else {
          alertify.error('Failed: An error occured');
        }
      });
    }
  }


  getDetails(index, data) {
    index = index - 1;
    // if (index !== -1) {
    //   let product =this.products[index];
    //   for (let key in data.value) {
    //       data.value[key]= product[key];
    //   }

      if (index < 0 || !Array.isArray(this.Gen_products) || !this.Gen_products[index]) {
        return;
      }
      this.selected = this.Gen_products[index];
      this.labels = this.safeJsonArray(this.selected['label_claim']);
      this.shelf_life = this.selected['shelf_life'] || '';
      this.packing_style = this.selected['packing_style'] || '';
      this.storage_condition = this.selected['storage_condition'] || '';
      this.color = this.selected['apperance'] || '';
     // }
  }

 
  getOuter(show) {
    if (show == 'yes') {
      this.isyesouter = true;
      this.isNoouter = false;
    } else if (show == 'no') {
      this.isyesouter = false;
      this.isNoouter = true;
    }
  }


  getClients(value) {
    if (value !== 'Own') {
      this.service.get('common.php?type=getClients').subscribe(response => {
        this.clients = response;
      });
    }
  }

  getClients1() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients1 = response;
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
  getGst() {
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gstList = this.toArray(response);
    });

  }
  onFileChanged3(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile3 = event.target.files[0];
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

  save(data) {
    let temp = data.value;
    console.log(temp);


    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append("product_type", "Formulation");
    uploadData.append("generic_name", this.selected['product_name']);
    uploadData.append("genericProductCode", this.selected['genericProductCode']);
    if (this.selectedFile1 !== undefined) {
      uploadData.append('product_lic', this.selectedFile1, this.selectedFile1.name);
    }

    // if (this.selectedFile2 !== undefined) {
    //   uploadData.append('fsc', this.selectedFile2, this.selectedFile2.name);
    // }

    // if (this.selectedFile3 !== undefined) {
    //   uploadData.append('copp', this.selectedFile3, this.selectedFile3.name);
    // }
    if (this.selectedFile4 !== undefined) {
      uploadData.append('photo', this.selectedFile4, this.selectedFile4.name);
    }
    if (this.selectedFile5 !== undefined) {
      uploadData.append('artwork_file', this.selectedFile5, this.selectedFile5.name);
    }
    if (this.selectedFile6 !== undefined) {
      uploadData.append('mopcup_file', this.selectedFile6, this.selectedFile6.name);
    }
    uploadData.append('artworkList', JSON.stringify(this.artworkList));
    uploadData.append('list', JSON.stringify(this.list));

    //  if (this.dosage_form == 'TABLET' || this.dosage_form == 'CAPSULE') {
    let label_claim = '';
    for (let i = 0; i < this.labels.length; i++) {
      let label = this.labels[i];
      if (label.equivalent_to !== '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + ' ' + label['strength'] + ' ' + label['unit'] + '\n';
      }
      if (label.equivalent_to == '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + '' + label['strength'] + ' ' + label['unit'] + '\n';
      }
    }
    label_claim += 'Excipient QS\nColor ' + this.color;
    uploadData.append('dose_unit_type', this.dose_unit_type);
    uploadData.append('dose_unit_qty', this.dose_unit_qty);
    uploadData.append('dose_unit_qty_unit', this.dose_unit_qty_unit);
    uploadData.append('label_claim', JSON.stringify(this.labels));
    // }

    this.service.post('master/product.php?type=saveBrandProductZuma', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product saved successfully');
        data.resetForm();
        // this.router.navigate(['/master/product/brand'])
        this.router.navigate(['/master/product/new-formulation-list']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  save_Saipro(data) {
    let temp = data.value;
    console.log(temp);

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append("product_type", "Formulation");
    uploadData.append("product_type", "Formulation");
    if (this.selectedFile1 !== undefined) {
      uploadData.append('product_lic', this.selectedFile1, this.selectedFile1.name);
    }

    if (this.selectedFile2 !== undefined) {
      uploadData.append('fsc', this.selectedFile2, this.selectedFile2.name);
    }

    if (this.selectedFile3 !== undefined) {
      uploadData.append('copp', this.selectedFile3, this.selectedFile3.name);
    }
    if (this.selectedFile4 !== undefined) {
      uploadData.append('photo', this.selectedFile4, this.selectedFile4.name);
    }
    if (this.selectedFile5 !== undefined) {
      uploadData.append('artwork_file', this.selectedFile5, this.selectedFile5.name);
    }
    if (this.selectedFile6 !== undefined) {
      uploadData.append('mopcup_file', this.selectedFile6, this.selectedFile6.name);
    }
    uploadData.append('artworkList', JSON.stringify(this.artworkList));
    uploadData.append('list', JSON.stringify(this.list));

    //  if (this.dosage_form == 'TABLET' || this.dosage_form == 'CAPSULE') {
    let label_claim = '';
    for (let i = 0; i < this.labels.length; i++) {
      let label = this.labels[i];
      if (label.equivalent_to !== '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + ' ' + label['strength'] + ' ' + label['unit'] + '\n';
      }
      if (label.equivalent_to == '') {
        label_claim = label['material_name'] + ' ' + label['grade'] + '' + label['strength'] + ' ' + label['unit'] + '\n';
      }
    }
    label_claim += 'Excipient QS\nColor ' + this.color;
    uploadData.append('dose_unit_type', this.dose_unit_type);
    uploadData.append('dose_unit_qty', this.dose_unit_qty);
    uploadData.append('dose_unit_qty_unit', this.dose_unit_qty_unit);
    uploadData.append('label_claim', JSON.stringify(this.labels));
    // }

    this.service.post('master/product.php?type=saveBrandProduct_saipro', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product saved successfully');
        data.resetForm();
        // this.router.navigate(['/master/product/brand'])
        this.router.navigate(['/master/product/new-formulation-list']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  checkStyle(value) {
    if (value == 'ADD NEW') {
      this.isStyle = true;
    } else {
      this.isStyle = false;
    }
  }

  saveStyle() {
    if (this.style.length !== 0) {
      this.service.get('qa/master.php?type=saveStyle&style=' + this.style).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('packing Style Saved Successfully!');
          this.getStyles();
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

  getStyles() {
    this.service.get('qa/master.php?type=getStyles').subscribe((response: any) => {
      this.styles = response;
    });
  }

  saveShelf() {
    this.shelf_life = this.shelf;
    if (this.shelf.length !== 0) {
      this.service.get('qa/master.php?type=saveShelf&shelf=' + this.shelf).subscribe(response => {
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

  getShelf() {
    this.service.get('qa/master.php?type=getShelfs').subscribe(response => {
      this.shelfs = response;
    });
  }
  saveDoseUnit() {
    if (this.dose_unit.length !== 0) {
      this.service.get('master/doseunit.php?type=saveDoseUnit&dose_unit=' + this.dose_unit).subscribe(response => {
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
      this.service.get('master/color.php?type=saveColor&color=' + this.product_color).subscribe(response => {
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
  packing_type;
  savepacking_type() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=savePacking&pack_type=' + this.packing_type).subscribe(response => {
        if (response['status'] == 'success') {
          this. getpacking_type();
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
    this.service.get('master/color.php?type=getpacking_type').subscribe(response => {
      this.getpacking_types = response;
    });
  }
  primary_packing_type;
  saveprimary_packing_type() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=saveprimary_packing_type&primary_packing_type=' + this.primary_packing_type).subscribe(response => {
        if (response['status'] == 'success') {
          this. getprimary_packing_type();
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
    this.service.get('master/color.php?type=getprimary_packing_type').subscribe(response => {
      this.getprimary_packing_types = response;
    });
  }
  add_sec_packing;
  saveadd_sec_packing() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=save_secondary_packings&save_secondary_packings=' + this.add_sec_packing).subscribe(response => {
        if (response['status'] == 'success') {
          this. getadd_secondary_packing();
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
    this.service.get('master/color.php?type=getsecondary_packings').subscribe(response => {
      this.getadd_secondary_packings = response;
    });
  }
  add_packs_size;
  saveadd_packs_size() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=save_pack_size&add_pack_size=' + this.add_packs_size).subscribe(response => {
        if (response['status'] == 'success') {
          this. getadd_pack_size();
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
    this.service.get('master/color.php?type=getpack_size').subscribe(response => {
      this.getadd_packs_sizes = response;
    });
  }



  add_nos_pouch;
  saveadd_nos_pouch() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=saveadd_nos_pouch&add_nos_pouch=' + this.add_nos_pouch).subscribe(response => {
        if (response['status'] == 'success') {
          this. getadd_nos_pouch();
          this.add_no_pouch = false;
          // this.packing_type = '';
          // this.color = '';
          alertify.success('packing_type saved successfully');

        } else {
          alertify.error(response['status']);
        }
      });

  }
  getadd_nos_pouchs;
  getadd_nos_pouch() {
    this.service.get('master/color.php?type=getnos_pouch').subscribe(response => {
      this.getadd_nos_pouchs = response;
    });
  }





  save_capsule_sizess;
  add_capsule_sizes() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=add_capsule_size&add_capsule_size=' + this.save_capsule_sizess).subscribe(response => {
        if (response['status'] == 'success') {
          this. get_capsule_size();
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
    this.service.get('master/color.php?type=get_capsule_size').subscribe(response => {
      this.get_capsule_sizes = response;
    });
  }



  add_mono_cartains;
  saveadd_mono_cartains() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=add_mono_cartains&add_mono_cartains=' + this.add_mono_cartains).subscribe(response => {
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
    this.service.get('master/color.php?type=get_add_mono_cartains').subscribe(response => {
      this.mono_cartains = response;
    });
  }






  add_mono_qtys;
  saveadd_mono_qty() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=saveadd_mono_qty&add_mono_qtys=' + this.add_mono_qtys).subscribe(response => {
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
    this.service.get('master/color.php?type=get_add_mono_qtys').subscribe(response => {
      this.mono_qtys = response;
    });
  }




  add_master_mono_qtys;
  saveadd_master_mono_qty() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=add_master_mono_qtys&add_master_mono_qtys=' + this.add_master_mono_qtys).subscribe(response => {
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
    this.service.get('master/color.php?type=get_add_master_mono_qtys').subscribe(response => {
      this.master_mono_qtyss = response;
    });
  }




  save_tertiary_packing;
  add_tertiary_packings() {
    // this.color = this.product_color;


      this.service.get('master/color.php?type=save_tertiary_packing&save_tertiary_packing=' + this.save_tertiary_packing).subscribe(response => {
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
    this.service.get('master/color.php?type=get_save_tertiary_packing').subscribe(response => {
      this.get_tertiary_packing = response;
    });
  }





















  addLabel(data,data1) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    if (!data1.valid) {
      alert('All fields are required!');
      return;
    }

    if (data.value['equivalent_to'] != '' && data.value['strength'] == '') {
      alert('Plase enter strength!');
      return;
    }

    if (!this.selected_material_grade) {
      alert('Please select material first!');
      return;
    }

    data.value['id'] = this.selected_material_grade['id']
    data.value['material_code'] = this.selected_material_grade['material_code'];
    data.value['grade'] = this.grade1;
    data.value['gradeName'] = this.grade1;
    data.value['dose_unit_qty_unit'] = this.dose_unit_qty_unit;
    data.value['dose_unit_qty'] = this.dose_unit_qty;
    data.value['dose_unit_name_txt'] = this.dose_unit_name_txt;
    data.value['dose_unit_type'] = this.dose_unit_type;
    data.value['each_unit_type'] = this.each_unit_type;
    data.value['apperance'] = this.apperance;
    this.labels[this.labels.length] = data.value;
    console.log(this.labels)
    data.resetForm();
     this.active_materials = [];
    this.active_materials = this.active_material;


    console.log(this.labels);
  }

  delLabel(index) {
    this.labels.splice(index, 1);
  }


  saveMrp(data) {
    this.service.post('master/mrp.php?type=saveMrp', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.reset();
        this.isMrp = false;
        alertify.success(' saved successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getMrp() {
    this.service.get('master/mrp.php?type=getMrp').subscribe((response: any) => {
      this.mrps = response;
    });
  }

  calculation() {
    this.qty = parseFloat((this.equivalancy_factor * this.strength).toFixed(2));


  }
  addMrp(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.mrp_list[this.mrp_list.length] = data.value;
    data.resetForm();
  }
  addSellingPrices(data) {
    if (!data.valid) {
      alert("All fiels are required");
      return;
    }
    this.sale_price_list[this.sale_price_list.length] = data.value;
    data.resetForm();
  }
  setEachUnit(each_unit_type) {
    if (each_unit_type == 'Qty') {
      this.dose_unit_type = '';
      this.ech_title = this.dose_unit_qty + '' + this.dose_unit_qty_unit;
    } else {
      if (this.dose_unit_type == 'Others') {
        this.dose_unit_type = this.dose_unit_name_txt;
      }
      this.dose_unit_qty = '';
      this.dose_unit_qty_unit = '';
      this.ech_title = this.dose_unit_type + ' ';
    }
  }

}
