import { Component, OnInit } from '@angular/core';
import { FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  version_no;
    flag1=false
    flag2=false

checkSpec(arg0: any) {

if(arg0 == 'New'){
  this.version_no = '00';
}




throw new Error('Method not implemented.');
}




  product_type = '';
  products;
  units;
  average_weight_unit='';
  each_unit_type = '';
  master_formula = 'New';
  fg_sub_materials;
  materials;
  packings;
  sub_types;
  formula_for='Dose Unit';
  qty;
  product_average_weight = "0";
  qty_overages_qty = "0";
  dose_pack;
  selected_product = null;
  average_weight = 0;
  materialList = [];
  packingList = [];
  selecteddosage = [];
  amaterials;
  dosages;
  dosage_form = ''
  prod_dosage_form = '';
  unit = '';
  stageListadd = [];
  selectedPacking = [];
  grades;
  grade = ''
  // selectedMaterial = [];
  selectedMaterial = {
    qty: '',
    gradeName: []  // Must be an array to hold multiple selected values
  };
  product_grade = '';
  percent_qty;
  min_per = 0;
  max_per = 0;
  overages_per=0;
  min_output_qty;
  revisionList = [];
  max_output_qty;
  formulaPer_unit='';
  formulaPer='';
  plant_type = '';
  batch_size_uom;
  formula_weight = 0;
  dose_unit_qty_unit = 0;
  doseunits;
  fg_sizes = [];
  fg_shapes = [];
  fg_sub_types = [];
  // formula_for: any;
  mfr_description: any;
  material_subtype: any;
  master_formula_type = 'Existing';
  plant_id: any;
  software_type: any;
  constructor(private service: DataAccessService, private router: Router) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  if (this.software_type == null) {
    this.service.getData('https://aurenyxgmp.com/admin/api/clients/client_data_without_token.php?type=get_client_data_by_id&id=' + localStorage.getItem("plant_id")).subscribe(response => {
      localStorage.setItem('client_info', JSON.stringify(response));
      this.plant_id = this.service.getPlantConfigFields('plant_id');
    });

  }
  }

  ngOnInit(): void {
    this.service.materialTypesChange();
    this.getMaterialSubType();
    this.getGrades()
    this.getUnits();
    this.getCONSUMABLESMATERIAL();
    this.getDoseUnits()
    this.getDosageTypes();
    this.getRole();
    this.get_staps();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.get_rights();
    this.getPackMaterialsBySubType('Primary Packing');
    this.getPackSize();

  }






  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +        localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.isuser=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      this.dept_head=this.rights[0].dept_head
      this.isauditor=this.rights[0].isauditor
      this.plant_head=this.rights[0].plant_head
      this.shift_allocator=this.rights[0].shift_allocator
    });
  }
  getDoseUnits() {
    this.service.get('master/doseunit.php?type=get_dose_units').subscribe(response => {
      this.doseunits = response;
    });
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
    this.service.get('master/product.php?type=getProductsByDosageForm&product_type=' + value).subscribe(response => {
      this.products = response;
    });

  }


  roles_data;
  steps_data;

  get_staps() {
    this.service.get('master/product.php?type=get_stages').subscribe(response => {
      this.steps_data = response;
    });
  }

  addStage = false;

  addStages(value){

    if(value == 'ADD NEW'){
      this.addStage = true;
    }

  }

  SaveStage(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('master/product.php?type=saveStages', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stage Saved!!!');
        this.get_staps();
        this.addStage = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }








  getRole() {
    this.service.get('master/product.php?type=getRole').subscribe(response => {
      this.roles_data = response;
    });
  }

  isROle = false;


  addRational(value){

    if(value== 'ADD NEW'){
      this.isROle = true;
    }

  }



  SaveRole(data) {
    let temp = data.value;
    this.service.post('master/product.php?type=Save_Role', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Rational / ROle Saved!!!');
        this.getRole();
        this.isROle = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  category ;
  selectedVendor=[];
  vendorName='';
  vendorcode='';
  selVendor(index){
    this.selectedVendor=this.selectedMaterial['vendorList'][index-1];
    this.vendorName=this.selectedVendor['vendor_name'];
    this.vendorcode=this.selectedVendor['supplier_code'];
  }

  getApprovedRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByTypeBOM&material_subtype=' + value).subscribe(response => {
      this.materials = response;
    });
    // if (this.category == "Active"  && value != "Excipients") {
    //   this.getAPIRawMaterials(value);
    // } else {
    //   this.getOtherRawMaterials(value);
    // }
  }
  getOtherRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByTypeBOM&material_subtype=' + value).subscribe(response => {
      this.materials = response;
    });
  }
  getAPIRawMaterials(value) {
    this.service.get('master/product.php?type=get_label_claim_product&id=' + this.selected_product['id']).subscribe(response => {
      if (response == null) {
        this.materials = [];
      } else {
        this.materials = response;
        this.product_average_weight = "0";
        for (let x = 0; x < this.materials.length; x++) {
          //factor
          if (Number(this.materials[x]['factor']) > 0) {
            var number = Number(this.product_average_weight) + (Number(this.materials[x]['strength']) * Number(this.materials[x]['factor']));
            this.product_average_weight = parseFloat(number.toString()).toFixed(4);
          } else {
            this.materials[x]['factor'] = 0;
            var number = Number(this.product_average_weight) + Number(this.materials[x]['strength']);
            this.product_average_weight = parseFloat(number.toString()).toFixed(4);
          }
        }
      }

    });
  }
  getApprovedRawMaterials1(value) {
    this.service.get('common.php?type=getMaterialsByTypeBOM&material_subtype=' + this.material_subtype).subscribe(response => {
      this.materials = response;
    });
    // if (value == "API") {
    //   this.getAPIRawMaterials(value);
    // } else {
    //   this.getOtherRawMaterials(value);
    // }
  }






  getDosageTypes() {
    // this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
    this.service.get('master/product.php?type=get_dosage_typesMFR').subscribe(response => {
      this.fg_sub_materials = response;
    });
  }

  isVdendor = false;
  getSelectedMaterial(index, type) {
    this.isVdendor = false;
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
      //  if (type == 'Active Pharmaceutical Ingredients') {
      //   if (Number(this.selectedMaterial['factor'] > 0)) {
      //     this.selectedMaterial['qty'] = (Number(this.selectedMaterial['factor']) * Number(this.selectedMaterial['strength'])).toFixed(4)
      //   } else {
      //     this.selectedMaterial['qty'] = Number(this.selectedMaterial['strength']).toFixed(6);
      //   }
      // } else {
      //   this.selectedMaterial['qty'] = '0';
      // }

      if(this.selectedMaterial['vendorList'].length > 0){
        this.isVdendor = true;
      }else{
        this.isVdendor = false;
      }

    }

  }
  selectedConsumeMaterial=[];
  getSelectedConsumeMaterial(index, type) {
    index = index - 1;
    if (index !== -1) {
      this.selectedConsumeMaterial = this.Consumematerials[index];


    }

  }
  getSelectedMaterial1(index, type) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
      if (type == 'Active Pharmaceutical Ingredients') {
        if (Number(this.selectedMaterial['factor'] > 0)) {
          this.selectedMaterial['qty'] = (Number(this.selectedMaterial['factor']) * Number(this.selectedMaterial['strength'])).toFixed(4)
        } else {
          this.selectedMaterial['qty'] = Number(this.selectedMaterial['strength']).toFixed(4);
        }
      } else {
        this.selectedMaterial['qty'] = '0';
      }
    }
    console.log(this.selectedMaterial);
  }

  // getSubMaterials() {
  //   let value = 'Raw Material';
  //   let idx = 0;
  //   this.service.observableMaterialTypes.subscribe(response => {
  //     for (let i = 0; i < response.length; i++) {
  //       if (response[i]['material_type'] == 'Raw Material') {
  //         idx = i;
  //       }
  //     }
  //     this.sub_types = [];
  //     this.sub_types = response[idx]['sub_materials'];


  //   });

  // }
  getMaterialSubType() {

    this.service.get('master/product.php?type=getMaterialSubType').subscribe(response => {
      this.sub_types = response;
    });
  }
  getMaterialsBySubType(value) {

    this.service.get('common.php?type=getMaterialsByTypeBOM&material_subtype=' + value + "&material_nature=").subscribe(response => {
      this.materials = response;
    });
  }

  getMaterial(value) {
    this.service.get('common.php?type=getMaterialsByTypeBOM&material_subtype=' + value).subscribe(response => {
      this.packings = response;
    });
  }

  getPackings(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedPacking = this.packings[index];
    }
  }
  ConvertedBatchSize=0;
  BatchSize_unit='Ltr'
  getBatchSize(value){
    //converting in kg
    if(value == 'Ml'){
    this.ConvertedBatchSize = value * 1000;
    }
    else if(value == 'Mg'){
      this.ConvertedBatchSize = value * 1000000;
    }
    else if(value == 'Gm'){
      this.ConvertedBatchSize = value * 1000;
    }
    else if(value == 'kg'){
      this.ConvertedBatchSize = value;
    }
    else{
      this.ConvertedBatchSize = 0;
    }
    console.log(this.ConvertedBatchSize);
  }
  BatchSize=0;
  add(data) {
    const invalid = [];
    const controls = data.controls;
    for (const name in controls) {
      if (controls[name].invalid) {
        invalid.push(name);
      }
    }
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (Number(this.average_weight) == 0 && this.prod_dosage_form != 'LIQUID') {
      alertify.error('Please enter batch size');
      return;
    }
    let temp = data.value;
    let bqty = 0;

    if (Number(temp['overages_per']) > 0) {
      temp['overage_qty'] = parseFloat(((Number(data.value['qty']) * Number(temp['overages_per'])) / 100) + '').toFixed(4);
    } else {
      temp['overage_qty'] = "0";
    }
    temp['total_qty'] = Number(temp['overage_qty']) + Number(temp['qty']);


  //  if (temp['yeild_contribution'] == 'Yes') {
      bqty = Number(this.formula_weight) + Number(data.value['qty']) + Number(temp['overage_qty']);
      // if ((bqty > Number(this.average_weight)) && this.prod_dosage_form != 'LIQUID') {
      //   alertify.error('Batch size not tally');
      //   return;
      // }
      console.log("1");

    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['material_code'] = this.selectedMaterial['material_code'];
    temp['vendor_name'] = this.selectedVendor['vendor_name'];
    temp['vendor_code'] = this.selectedVendor['supplier_code'];
    temp['gradeName'] = this.selectedMaterial['gradeName'];
    temp['unit2'] =='kg';
    let qty = Number(temp['qty']) || 0;
    let avgWeight = Number(this.average_weight) || 0;
    let formulaPer = Number(this.formulaPer) || 1; // avoid division by 0

    let B = (qty * avgWeight) / formulaPer;
    console.log('unit :>> ',temp['unit'] );
    temp['Qty1']=B;



      if(this.BatchSize_unit == 'Ltr'){

     if(temp['unit'] == 'mg'){
      let con_unit =(this.BatchSize * 1000);
      let ans = temp['total_qty']*con_unit/formulaPer;
      let finalSave=ans/1000000
      temp['Qty2']=finalSave;
      console.log('con_unit :>> ', con_unit );
      console.log('c1 :>> ', ans );

      }else if(temp['unit'] == 'gm' || temp['unit'] == 'ml'){
        let con_unit =this.BatchSize * 1000;
        let ans = temp['total_qty']*con_unit/formulaPer;

        let finalSave=ans/1000
        temp['Qty2']=finalSave;
        console.log('con_unit :>> ', con_unit );
        console.log('c11 :>> ', ans );
      }

    }else if(this.BatchSize_unit == 'KG'){
      if(temp['unit'] == 'mg'){
        let con_unit =this.BatchSize * 1000;
        let ans = temp['total_qty']*con_unit/formulaPer;
        let finalSave=ans/1000000
        temp['Qty2']=finalSave;
        console.log('con_unit :>> ', con_unit );
        console.log('c12 :>> ', ans );

        }else if(temp['unit'] == 'gm' || temp['unit'] == 'ml'){
          let con_unit =this.BatchSize * 1000;
          let ans = temp['total_qty']*con_unit/formulaPer;

          let finalSave=ans/1000
          console.log('con_unit :>> ', con_unit );
          temp['Qty2']=finalSave;
          console.log('c13 :>> ', ans );
        }

    }

    if(temp['qty']=='q.s.'){
      if(temp['overages_per']=='q.s.'){
         temp['overages_per'] ='q.s.'
         temp['Qty2']='--';
      }else{
        temp['overages_per'] ='--'
        temp['Qty2']='q.s. to'+ ' '+this.BatchSize + ' '+this.BatchSize_unit;
      }
      temp['total_qty']='q.s.';
      temp['overage_qty'] = "0";

    }
    // }else if(temp['unit'] == 'mg'){
    //   let c = B / 1000000;
    //   temp['Qty2']=c;
    //   console.log('c :>> ', c);
    // }else if(temp['unit'] == 'gm'){
    //   let c = B / 1000;
    //   temp['Qty2']=c;
    //   console.log('c :>> ', c);
    // }




console.log('B :>> ', B);





    this.materialList[this.materialList.length] = temp;
    if (temp['yeild_contribution'] == 'Yes') {
      this.formula_weight = Number(this.formula_weight) + Number(data.value['total_qty']);
      console.log("2");

    }
    else{
      this.formula_weight += data.value['total_qty'];
      // Math.round(this.formula_weight).toFixed(4)
      console.log("3");

    }
    Math.round(this.formula_weight);
    data.resetForm();
    this.materials = [];
  }
  addRevision(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.revisionList[this.revisionList.length] = temp;


  }

  dele(index) {
    this.materialList.splice(index, 1);
    let qty = 0;
    for (let i = 0; i < this.materialList.length; i++) {
      qty = Number(qty) + Number(this.materialList[i]['qty']);
      // this.formula_weight =  Number(qty);
    }
    this.formula_weight = qty;
    console.log(qty);
    console.log(this.formula_weight);
  }

  addPage(data) {
    if (Number(this.average_weight) == 0 && this.prod_dosage_form != 'LIQUID') {
      alertify.error('Please enter batch size');
      return;
    }
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let bqty = ((this.formula_weight) + (data.value['qty_overages_qty'])).toFixed(4);
    if (bqty > Number(this.average_weight)) {
      alertify.error('Batch size not tally');
      return;
    }
    let temp = data.value;
    temp['material_name'] = this.selectedPacking['material_name'];
    this.packingList[this.packingList.length] = temp;
    this.formula_weight = bqty;
    //  Math.round(this.formula_weight);
    data.resetForm();
  }

  deletestage(index) {
    this.stageListadd.splice(index, 1);
  }

  strength
  pm_batch_size
  strength_unit
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

    let temp = data.value;
    if (temp['bom_type'] == 'Fresh Batch') {
      if (this.materialList.length == 0) {
        alertify.error('Raw Materials are required');
        return;
      }
    }

    temp['raw_materials'] = this.materialList;
    temp['BatchSize'] = this.BatchSize;
    temp['BatchSize_unit'] = this.BatchSize_unit;
    temp['packing_materials'] = this.packingList;
    temp['consumeableMaterial'] = this.ConsumeList;
    temp['product_type'] = temp['product_type'];
    temp['unit'] = this.unit;
    temp['dose_pack'] = this.dose_pack;
    temp['strength'] = this.strength;
    temp['strength_unit'] = this.strength_unit;
    temp['pm_batch_size'] = this.pm_batch_size;

    this.service.post('production/master.php?type=saveMFR_FormulationZuma', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product MFR initiated successfully!');
        this.router.navigate(['/unitformula/log']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


  calculate() {
    //Min Yeild
    let percent = (Number(this.average_weight) * Number(this.min_per)) / 100

    this.min_output_qty = parseFloat(percent + '').toFixed(4);

    //Max Yeild
    percent = (Number(this.average_weight) * Number(this.max_per)) / 100

    this.max_output_qty = parseFloat(percent + '').toFixed(4);

  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  LoadPoduct(idx) {
    this.selected_product = this.products[idx - 1];
    this.prod_dosage_form = this.selected_product['dosage_type'];
    if(this.selected_product['UnitFormula'] == 'Present'){
      alertify.error('Unit Formula is present for this product');
      return;
    }else{
      alertify.success('Unit Formula is not present for this product');
    }
  }
  calcPercentage(data) {
    if (this.prod_dosage_form != 'LIQUID') {
      if (Number(this.average_weight) < Number(this.product_average_weight)) {
        alert('Average Weight should be greater than API products weight ->' + this.product_average_weight);
        return;
      }
    }
    let temp = data.value;
  if(temp['qty'] != 'q.s.'){

    if (Number(temp['overages_per']) > 0) {
      temp['qty_overages_qty'] = parseFloat(((Number(data.value['qty']) * Number(temp['overages_per'])) / 100) + '').toFixed(6);
    } else {
      temp['qty_overages_qty'] = "0";
    }
    this.qty_overages_qty = parseFloat((Number(temp['qty_overages_qty']) + Number(temp['qty'])).toString()).toFixed(6);

    this.percent_qty = parseFloat(((Number(this.qty_overages_qty) / Number(this.average_weight)) * 100) + '').toFixed(6);
    console.log((temp['qty_overages_qty']));
    console.log((temp['qty']));
    console.log("hello");
    console.log((this.qty_overages_qty));
    console.log((this.average_weight));
  }else{
    temp['qty_overages_qty'] = "0";
    this.qty_overages_qty = Number(0).toString();

    this.percent_qty =0

  }

  }

  calcqty(data) {
    this.percent_qty = ((this.qty / this.average_weight) * 100);
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

  onValueChange(newValue: string) {
    const regex = /^[0-9]+$/;
    const isValidRegex = regex.test(newValue);

    if (isValidRegex) {

      this.flag1=false
    } else {


      this.flag1=true
      if(newValue==''|| null)
        {
          this.flag1=false
        }
    }
    if(this.flag1==true)
      {
        this.flag2=true

      }
      if(this.flag1==false)
        {
        this.flag2=false
        }


        if(newValue=='GM'){
          this.formulaPer_unit= newValue;
          this.BatchSize_unit='KG';
        }else if(newValue=='ML'){
          this.formulaPer_unit= newValue;
          this.BatchSize_unit='Ltr';
        }

  }

  onConverion(newValue){
    if(newValue=='GM'){
      this.formulaPer_unit= newValue;
      this.BatchSize_unit='KG';
    }
    else if(newValue=='ML'){
      this.formulaPer_unit= newValue;
      this.BatchSize_unit='Ltr';
    }
    else if(newValue=='MG'){
      this.formulaPer_unit= newValue;
      this.BatchSize_unit='KG';
    }
  }


//  Packing Material
//  Packing Material
//  Packing Material
//  Packing Material
//  Packing Material
//  Packing Material
//
//
//
//
//



Packmaterials;

getPackMaterialsBySubType(value) {

  this.service.get('common.php?type=getMaterialsByType1&material_subtype=Primary Packing Material' + "&material_nature=").subscribe(response => {
    this.Packmaterials = response;
  });
}
Consumematerials;
getCONSUMABLESMATERIAL() {

  this.service.get('common.php?type=getMaterialsByTypeBOM&material_subtype=Consumables').subscribe(response => {
    this.Consumematerials = response;
  });
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
  temp['material_code'] = this.selectedPackMaterial['material_code'];
  temp['material_name'] = this.selectedPackMaterial['material_name'];
  temp['grade'] = this.selectedPackMaterial['grade'];
  this.packingList[this.packingList.length] = temp;
  data.resetForm();
}
delpackingList(index) {
  this.packingList.splice(index, 1);
}
ConsumeList=[];
addconsumeaterial(data) {
  if (!data.valid) {
    alert('All fields are required');
    return;
  }
  let temp = data.value;
  temp['role'] = 'Consumables';
  if (Number(temp['overages']) > 0) {
    let percent = ((Number(temp['qty'] * temp['overages'])) / 100);
    percent = Number(temp['qty']) + percent;
    temp['total_qty'] = parseFloat(percent + '').toFixed(2);
  } else {
    temp['total_qty'] = temp['qty'];
  }
  temp['material_code'] = this.selectedConsumeMaterial['material_code'];
  temp['material_name'] = this.selectedConsumeMaterial['material_name'];
  temp['grade'] = this.selectedPackMaterial['grade'] || 'NA';
  this.ConsumeList[this.ConsumeList.length] = temp;
  data.resetForm();
}
delconsumeist(index) {
  this.ConsumeList.splice(index, 1);
}
pack_size
getPackSize() {
  this.service.get('common.php?type=getPackSizes').subscribe(response => {
    this.pack_size = response;
  });
}

selectedPackMaterial=[];
getSelectedPackMaterial(index, type) {
  index = index - 1;
  if (index !== -1) {
    this.selectedPackMaterial = this.Packmaterials[index];
    if (type == 'Active Pharmaceutical Ingredients' ){
            if (Number(this.selectedPackMaterial['factor'] > 0)) {
        this.selectedPackMaterial['qty'] = (Number(this.selectedPackMaterial['factor']) * Number(this.selectedPackMaterial['strength'])).toFixed(2)
      } else {
        this.selectedPackMaterial['qty'] = Number(this.selectedPackMaterial['strength']).toFixed(2);
      }
    } else {
      this.selectedPackMaterial['qty'] = '0';
    }
  }
}

}
