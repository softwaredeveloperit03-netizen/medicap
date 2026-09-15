
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  listTitle = 'Unit Formula Log';
  closeRoute = '/unitformula';
  isPrepareHub = false;

  isView = false;
  sub_materials;
  average_weight;
  isEdit = false;
  add_packing_material = false;
  results;
  packingList = [];
  packing_List_All = [];
  selectedResult = [];
  label_claims = [];
  sub_types;
  dosage_form = '';
  unit_name = '';
  pack_size;
  product_code = '';
  status = '';
  raw_materials = [];
  materials;
  dosages;
  products;
  material_Code;
  plant_type = '';
  plant_id = '';
  selectedMaterial = [];
  countries;
  material_type;
  dose_unit_type;
  master_formula_type;
  formula_for;

  average_weight_unit;
  formula_weight;
  batch_size_uom;
  mfr_description;
  product_type;
  batch_size;
  input_qty_tally;
  spec_no;
  version_no;
  review_date;
  supersede_no;
  material_subtype;
  material_name;
  fg_sub_materials;
  percent_qty;
  qty;



  units;
  each_unit_type = '';
  master_formula = 'New';
  packings;
  product_average_weight = "0";
  qty_overages_qty = "0";
  selected_product = null;
  materialList = [];
  selecteddosage = [];
  amaterials;
  prod_dosage_form = '';
  unit = '';
  stageListadd = [];
  selectedPacking = [];
  grades;
  grade = ''
  product_grade = '';
  min_per = 0;
  max_per = 0;
  min_output_qty;
  revisionList = [];
  max_output_qty;
  dose_unit_qty_unit = 0;
  doseunits;
  fg_sizes = [];
  fg_shapes = [];
  fg_sub_types = [];

  loggedInDept;
  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.isPrepareHub = (this.router.url || '').includes('/master/bill-of-material/prepare');
    if (this.isPrepareHub) {
      this.listTitle = 'Prepare Batch Formula';
      this.closeRoute = '/master/bill-of-material';
    }
    this.getSubMaterials();
    this.getUnitFormulas();
    this.getPackSize();
    this.getCountries();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.service.materialTypesChange();
    this.getGrades()
    this.getUnits();
    this.getDoseUnits()
    this.getDosageTypes();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.get_rights();

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
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id')+'&dep_name='+ this.loggedInDept).subscribe(response  => {
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
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }

  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }

  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;

  }
  // ---------------------------------------------------------------------//
  delpackingList(index){

      this.packingList.splice(index,1);

  }
  checkform() {

    if (this.plant_type == 'Formulation') {
      this.router.navigate(['/unitformula/new-form']);
    } else {
      this.router.navigate(['/unitformula/new-api']);
    }
  }
  getCountries() {
    this.service.get('master/country.php?type=getCountries').subscribe(response => {
      this.countries = response;
    })
  }
  getPackSize() {
    this.service.get('common.php?type=getPackSizes').subscribe(response => {
      this.pack_size = response;
    });
  }
  getUnitFormulas() {
    this.service.get('production/unitformula.php?type=getUnitFormulaLogZuma').subscribe(response => {
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
  viewPackingMaterial(index) {
    this.selectedResult = this.results[index];
    this.isView = false;
    this.add_packing_material = true;
    this.packing_List_All = (this.selectedResult['packing_configuration']);
    this.primary_pm_list = (this.selectedResult['primary_pm_list']);
    this.consumeableMaterial = (this.selectedResult['consumeableMaterial']);
    this.label_claims = JSON.parse(this.selectedResult['label_claim']);
    if (this.packing_List_All == null) {
      this.packing_List_All = [];
    }



this.getBrandProducts();

  }

  brand_names
  getBrandProducts() {
    this.service.get('master/product.php?type=getBrandProductsNameZuma&product_code=' + this.selectedResult['product_code']).subscribe(response => {
      this.brand_names = response;
    });
  }
  groupedMaterials =[];
  primary_pm_list =[];
  consumeableMaterial=[];
  packingConfigurations: any[] = [];
  revisionHistory: any[] = [];
  versionControl: any = {};

  get rawMaterialsColspan(): number {
    let cols = 8; // sr, type, code, name, qty, unit, total, yield
    if (this.plant_id !== '59' && this.plant_id !== '58') {
      cols += 2;
    }
    if (this.plant_type === 'Formulation') {
      cols += 2;
    }
    cols += 1; // LOD
    return cols;
  }

  displayValue(value: any): string {
    if (value === null || value === undefined || String(value).trim() === '') {
      return 'NA';
    }
    return String(value);
  }

  displayPercent(value: any): string {
    if (value === null || value === undefined || String(value).trim() === '') {
      return 'NA';
    }
    const num = Number(value);
    if (isNaN(num)) {
      return String(value);
    }
    return num.toFixed(2);
  }

  displayYieldQty(savedQty: any, percent: any): string {
    if (savedQty !== null && savedQty !== undefined && String(savedQty).trim() !== '') {
      return String(savedQty);
    }
    const batch = Number(this.selectedResult?.['batch_size']);
    const per = Number(percent);
    if (!isNaN(batch) && !isNaN(per) && batch > 0) {
      return String((batch * per) / 100);
    }
    return 'NA';
  }

  displayPackQty(qty: any, unit: any): string {
    if (qty === null || qty === undefined || String(qty).trim() === '') {
      return 'NA';
    }
    const unitText = unit ? ' ' + unit : '';
    return String(qty) + unitText;
  }

  view(index) {

    this.groupedMaterials=[];

    this.selectedResult = this.results[index];
    this.raw_materials = (this.selectedResult['raw_materials']) || [];
    this.primary_pm_list = (this.selectedResult['primary_pm_list']) || [];
    this.consumeableMaterial = (this.selectedResult['consumeableMaterial']) || [];
    this.packingConfigurations = Array.isArray(this.selectedResult['packing_configuration'])
      ? this.selectedResult['packing_configuration']
      : [];

    // Ensure raw_materials is an array
    if (!Array.isArray(this.raw_materials)) {
      this.raw_materials = [];
    }

    this.groupedMaterials = this.raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});

    this.revisionHistory = this.parseJsonArray(this.selectedResult['revison_history'] || this.selectedResult['revisionList']);
    this.versionControl = {
      supersede_no: this.selectedResult['supersede_no'] || '',
      supersede_ver_no: this.selectedResult['supersede_ver_no'] || '',
      review_date: this.selectedResult['review_date'] || '',
    };
    if (this.revisionHistory.length) {
      const last = this.revisionHistory[this.revisionHistory.length - 1] || {};
      this.versionControl.supersede_no = this.versionControl.supersede_no || last['supersede_no'] || '';
      this.versionControl.supersede_ver_no = this.versionControl.supersede_ver_no || last['supersede_ver_no'] || last['ver_no'] || '';
      this.versionControl.review_date = this.versionControl.review_date || last['review_date'] || last['effective_date'] || '';
      if (!this.selectedResult['version_no'] && (last['ver_no'] || last['version_no'])) {
        this.selectedResult['version_no'] = last['ver_no'] || last['version_no'];
      }
    }

    this.isView = true;
    console.log(this.raw_materials);
  }
  // view(index) {
  //   this.selectedResult = this.results[index];
  //   this.isView = true;
  // }
  edit(index) {
    this.selectedResult = this.results[index];

    this.dosage_form=this.selectedResult['dosage_form'];
    this.material_Code=this.selectedResult['material_Code'];
    this.material_type=this.selectedResult['material_type'];
    this.product_code=this.selectedResult['product_code'];
    this.product_type=this.selectedResult['product_type'];
    this.formula_for=this.selectedResult['formula_for'];
    this.average_weight=this.selectedResult['average_weight'];
    this.average_weight_unit=this.selectedResult['average_weight_unit'];
    this.formula_weight=this.selectedResult['formula_weight'];
    this.dose_unit_type=this.selectedResult['dose_unit_type'];
    this.average_weight_unit=this.selectedResult['average_weight_unit'];
    this.batch_size_uom=this.selectedResult['batch_size_uom'];
    this.master_formula_type=this.selectedResult['master_formula_type'];
    this.batch_size=this.selectedResult['batch_size'];
    this.input_qty_tally=this.selectedResult['input_qty_tally'];
    this.version_no=this.selectedResult['version_no'];
    this.spec_no=this.selectedResult['spec_no'];
    this.review_date=this.selectedResult['review_date'];
    this.supersede_no=this.selectedResult['supersede_no'];
    this.material_subtype=this.selectedResult['material_subtype'];
    this.material_name=this.selectedResult['material_name'];
    this.formula_for=this.selectedResult['formula_for'];
    this.isEdit=true;
    this.isView=false;
    console.log(this.selectedResult)
  }

  download() {
    this.service.open('production/unitformula.php?type=downloadUnitFormla&id=' + this.selectedResult['id']);
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
    temp['material_code'] = this.selectedMaterial['material_code'];
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['grade'] = this.selectedMaterial['grade'];
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }
  brand_name;
  savePackingMaterial(form) {

    if (this.packingList.length == 0) {
      alertify.error('Please add packing material');
      return
    }
    if (!form.valid) {
      alertify.error('All Fields are mandatory');
      return
    }

    let temp = form.value;

    for (let x = 0; x < this.packing_List_All.length; x++) {
      if (this.packing_List_All[x]['pm_pack_size'] == temp['pack_size']['pack_size'] &&
        this.packing_List_All[x]['pm_pack_unit'] == temp['pack_size']['unit']) {
        alertify.error(temp['pack_size']['pack_size'] + '-' + temp['pack_size']['unit'] + ' Already exists. Duplicates not allowed');
        return;
      }
    }


    // temp['packing_materials'] = this.packingList;
    // temp['pm_pack_size'] = temp['pack_size']['pack_size'];
    // temp['pm_pack_unit'] = temp['pack_size']['unit'];




    let obj = {
      "packing_materials": this.packingList,
      "pack_size": temp['pack_size']['pack_size'],
      "unit": temp['pack_size']['unit'],
      "batch_size": temp['pm_batch_size'],
      "market_type": temp['market_type'],
      "country_specific": temp['country_specific'],
      "country_name": temp['country_name'],
      "packing_type": temp['packing_type'],
      "unit_formula_id": this.selectedResult['id'],
      "brand_name": this.brand_name

    }

console.log(obj);


    this.service.post('production/master.php?type=save_packing_materialZuma&id=' + this.selectedResult['id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Packing material details has been successfully!');
        this.isView = false;
        this.getUnitFormulas();
        this.add_packing_material = false;
        this.packingList = [];
        form.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }
  delPackingMaterial(id){
    this.service.get('production/master.php?type=deleteMFR&id='+id).subscribe(response=>{
      if(response['status']){
        alertify.success('Material Deleted Successuly');
        this.getUnitFormulas();
      }else{
        alertify.error('some error occured');
      }
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

getApprovedRawMaterials(value) {
  // this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe(response => {
  //   this.materials = response;
  // });
  if (value == "API") {
    this.getAPIRawMaterials(value);
  } else {
    this.getOtherRawMaterials(value);
  }
}
getApprovedRawMaterials1(value) {
  this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe(response => {
    this.materials = response;
  });
  // if (value == "API") {
  //   this.getAPIRawMaterials(value);
  // } else {
  //   this.getOtherRawMaterials(value);
  // }
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
          this.product_average_weight = parseFloat(number.toString()).toFixed(2);
        } else {
          this.materials[x]['factor'] = 0;
          var number = Number(this.product_average_weight) + Number(this.materials[x]['strength']);
          this.product_average_weight = parseFloat(number.toString()).toFixed(2);
        }
      }
    }

  });
}


getOtherRawMaterials(value) {
  this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe(response => {
    this.materials = response;
  });
}

getDosageTypes() {
  this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
    this.fg_sub_materials = response;
  });
}


getSelectedMaterial(index, type) {
  index = index - 1;
  if (index !== -1) {
    this.selectedMaterial = this.materials[index];
    if (type == 'Active Pharmaceutical Ingredients' ){
            if (Number(this.selectedMaterial['factor'] > 0)) {
        this.selectedMaterial['qty'] = (Number(this.selectedMaterial['factor']) * Number(this.selectedMaterial['strength'])).toFixed(2)
      } else {
        this.selectedMaterial['qty'] = Number(this.selectedMaterial['strength']).toFixed(2);
      }
    } else {
      this.selectedMaterial['qty'] = '0';
    }
  }
}
getSelectedMaterial1(index, type) {
  index = index - 1;
  if (index !== -1) {
    this.selectedMaterial = this.materials[index];
    if (type == 'Active Pharmaceutical Ingredients'
          ){}      if (Number(this.selectedMaterial['factor'] > 0)) {
        this.selectedMaterial['qty'] = (Number(this.selectedMaterial['factor']) * Number(this.selectedMaterial['strength'])).toFixed(2)
      } else {
        this.selectedMaterial['qty'] = Number(this.selectedMaterial['strength']).toFixed(2);
      }
    } else {
      this.selectedMaterial['qty'] = '0';
    }
  }


getSubMaterials() {
  this.service.get('production/unitformula.php?type=getPM_SubTypes').subscribe(response => {
    this.sub_types = response;
  });

}
getMaterialsBySubType(value) {

  this.service.get('common.php?type=getMaterialsByType1&material_subtype=' + value + "&material_nature=").subscribe(response => {
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
    temp['overage_qty'] = parseFloat(((Number(data.value['qty']) * Number(temp['overages_per'])) / 100) + '').toFixed(2);
  } else {
    temp['overage_qty'] = "0";
  }
  temp['total_qty'] = Number(temp['overage_qty']) + Number(temp['qty']);


//  if (temp['yeild_contribution'] == 'Yes') {
    bqty = Number(this.formula_weight) + Number(data.value['qty']) + Number(temp['overage_qty']);
    if ((bqty > Number(this.average_weight)) && this.prod_dosage_form != 'LIQUID') {
      alertify.error('Batch size not tally');
      return;
    }
//  }

  temp['material_name'] = this.selectedMaterial['material_name'];
  temp['material_code'] = this.selectedMaterial['material_code'];
  this.materialList[this.materialList.length] = temp;
  if (temp['yeild_contribution'] == 'Yes') {
    this.formula_weight = Number(this.formula_weight) + Number(data.value['total_qty']);
  }
  data.resetForm();
  this.materials = [];
}
addRevision(data){
  let temp = data.value;
  this.revisionList[this.revisionList.length] = temp;


}

dele(index) {
  this.materialList.splice(index, 1);
  let qty = 0;
  for (let i = 0; i < this.materialList.length; i++) {
    qty = Number(qty) + Number(this.materialList[i]['qty_overages_qty']);
  }
  this.formula_weight = qty;
}

deletestage(index) {
  this.stageListadd.splice(index, 1);
}
del(index) {
  this.packingList.splice(index, 1);
}


calculate() {
  //Min Yeild
  let percent = (Number(this.average_weight) * Number(this.min_per)) / 100

  this.min_output_qty = parseFloat(percent + '').toFixed(2);

  //Max Yeild
  percent = (Number(this.average_weight) * Number(this.max_per)) / 100

  this.max_output_qty = parseFloat(percent + '').toFixed(2);

}
checkValue(event) {
  if (event.target.value < 0) {
    event.target.value = 0;
  }
}
LoadPoduct(idx) {
  this.selected_product = this.products[idx - 1];
  this.prod_dosage_form = this.selected_product['dosage_type'];
}
calcPercentage(data) {

  if (this.prod_dosage_form != 'LIQUID') {
    if (Number(this.average_weight) < Number(this.product_average_weight)) {
      alert('Average Weight should be greater than API products weight ->' + this.product_average_weight);
      return;
    }
  }
  let temp = data.value;

  if (Number(temp['overages_per']) > 0) {
    temp['qty_overages_qty'] = parseFloat(((Number(data.value['qty']) * Number(temp['overages_per'])) / 100) + '').toFixed(2);
  } else {
    temp['qty_overages_qty'] = "0";
  }
  this.qty_overages_qty = parseFloat((Number(temp['qty_overages_qty']) + Number(temp['qty'])).toString()).toFixed(2);

  this.percent_qty = parseFloat(((Number(this.qty_overages_qty) / Number(this.average_weight)) * 100) + '').toFixed(2);;
}

calcqty(data) {
  this.percent_qty = (this.qty / this.average_weight) * 100;
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













prepareBF=false;
PrepareBF(index) {
  this.selectedResult = this.results[index];
  this.isView = false;
  // this.prepareBF = true;
  this.isprepareBFView = true;
  this.packing_List_All = (this.selectedResult['packing_configuration']);
  this.label_claims = this.selectedResult['label_claim'];
  if (this.packing_List_All == null) {
    this.packing_List_All = [];
  }

  this.getBrandProducts();
  // Load full formula row from API; do not call viewisprepareBFView here — it ran before HTTP
  // completed and bfr_results was still empty or a previous row.
  this.get_approved_UnitFormulasZuma();
}

bfr_results;
get_approved_UnitFormulasZuma() {
  this.service.get('production/unitformula.php?type=get_approved_UnitFormulasZuma&product_code='+this.selectedResult['product_code']+'&id='+this.selectedResult['id']).subscribe(response => {
    this.bfr_results = response;
    this.isView = false;
    this.add_packing_material = false;
    this.prepareBF = false;
    this.isprepareBFView = true;
    this.prepare_batch = true;

    const record = this.pickBfrRecord(response, this.selectedResult['id']);
    this.applyBfrRecord(record);
    console.log('selectedResultbfr_results :>> ', this.selectedResultbfr_results);
  });
}
avg_unit_weight;
prepare_batch=false;
isprepareBFView=false;
selectedResultbfr_results=[];

/** API may return one object or an array of unitformula rows — pick the row for the open grid item. */
private pickBfrRecord(response: any, matchId?: string | number): any {
  if (response == null) {
    return {};
  }
  const idStr = matchId != null && matchId !== '' ? String(matchId) : null;
  if (Array.isArray(response)) {
    if (idStr) {
      const found = response.find((r: any) => r && String(r.id) === idStr);
      if (found) {
        return found;
      }
    }
    return response[0] || {};
  }
  return response;
}

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

/** packing_configuration may be JSON string or already an array */
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

/**
 * Apply one unitformula record to the Master Formula Details / BOM UI.
 * Merges with selectedResult so bom_*, label, etc. from the list row stay available if API omits them.
 */
private applyBfrRecord(record: any) {
  const sr = this.selectedResult as any;
  const base =
    sr && typeof sr === 'object' && !Array.isArray(sr)
      ? { ...sr }
      : {};
  this.selectedResultbfr_results =
    record && typeof record === 'object' && !Array.isArray(record)
      ? { ...base, ...record }
      : { ...base };

  this.avg_unit_weight = Number(this.selectedResultbfr_results['average_weight']) || 0;

  this.raw_materials = this.parseJsonArray(this.selectedResultbfr_results['raw_materials']);

  for (let i = 0; i < this.raw_materials.length; i++) {
    if (
      this.raw_materials[i]['total_qty'] == 'q.s.' ||
      this.raw_materials[i]['total_qty'] == '--'
    ) {
      this.raw_materials[i]['percent_qty'] = this.raw_materials[i]['total_qty'];
    } else {
      this.raw_materials[i]['percent_qty'] = Number(
        this.raw_materials[i]['total_qty']
      ).toFixed(4);
    }
  }

  this.packing_List_All = this.parsePackingConfiguration(
    this.selectedResultbfr_results['packing_configuration']
  );
  this.primary_pm_list = this.parseJsonArray(
    this.selectedResultbfr_results['primary_pm_list']
  );
  this.consumeableMaterial = this.parseJsonArray(
    this.selectedResultbfr_results['consumeableMaterial']
  );

  this.groupedMaterials = this.raw_materials.reduce((group, material) => {
    const { stage } = material;
    const key = stage != null && stage !== '' ? stage : 'Default';
    group[key] = group[key] ?? [];
    group[key].push(material);
    return group;
  }, {});
}

viewisprepareBFView(index, is_prepare_batch) {
  this.isView = false;
  this.add_packing_material = false;
  this.prepareBF = false;
  this.isprepareBFView = true;
  this.prepare_batch = !!is_prepare_batch;

  const list = Array.isArray(this.bfr_results)
    ? this.bfr_results
    : this.bfr_results
      ? [this.bfr_results]
      : [];
  const record = list[index] ?? {};
  this.applyBfrRecord(record);
  console.log('selectedResultbfr_results :>> ', this.selectedResultbfr_results);
}

calculateBatchQty(batch_size, mat_type) {
  this.groupedMaterials =[];

  // Ensure raw_materials is an array
  if (!this.raw_materials || !Array.isArray(this.raw_materials)) {
    this.raw_materials = [];
  }

  // Get base batch size for calculations
  let base_batch_size = Number(this.selectedResult['bom_batch_size']) || Number(this.selectedResultbfr_results['bom_batch_size']) || 1;
  
  // Calculate for Raw Materials
  if (mat_type == 'RM' || mat_type == 'ALL') {
    var rm_batch_size : Number =batch_size;
    for (let i = 0; i < this.raw_materials.length; i++) {
      let batch_qty = 0;
      let avgUnit_weight = 1;

      let formula_for = this.selectedResult['formula_for'] || this.selectedResultbfr_results['formula_for'];
      if (formula_for != 'Unit') {
        avgUnit_weight = Number(this.avg_unit_weight);
      }

      // Calculate qty in mg/ml equivalent
      let final_qty_mg: number = 
        (Number(batch_size) * Number(this.raw_materials[i]['Qty2'])) / base_batch_size;
      let f_batch_qty: number = (+final_qty_mg);
      batch_qty = (+f_batch_qty);

      if (
        this.raw_materials[i]['total_qty'] == 'q.s.' ||
        this.raw_materials[i]['total_qty'] == '--'
      ) {
        this.raw_materials[i]['batch_qty'] = this.raw_materials[i]['total_qty'];
      } else {
        // Base qty
        let convertedQty = parseFloat(batch_qty + '');

        // Check unit and convert
        let unit = (this.raw_materials[i]['unit'] || '').toLowerCase();

        switch (unit) {
          case 'mg':
            convertedQty = convertedQty / 1_000; // mg → kg
            this.raw_materials[i]['Converted_unit'] = 'Gm';
            break;

          case 'gm':
          case 'g':
            convertedQty = convertedQty / 1_000; // g → kg
            this.raw_materials[i]['Converted_unit'] = 'Kg';
            break;

          case 'kg':
            // already in kg
            this.raw_materials[i]['Converted_unit'] = 'Kg';
            break;

          case 'ml':
            convertedQty = convertedQty / 1_000; // ml → L
            this.raw_materials[i]['Converted_unit'] = 'Ltr';
            break;

          case 'ltr':
          case 'l':
            this.raw_materials[i]['Converted_unit'] = 'Ltr';
            break;

          default:
            // leave unchanged if no conversion rule
            break;
        }

        this.raw_materials[i]['batch_qty'] = convertedQty.toFixed(4);
      }
    }
  }

  // Calculate for Consumables Materials
  if (mat_type == 'CONSUMABLE' || mat_type == 'ALL') {
    if (this.consumeableMaterial && Array.isArray(this.consumeableMaterial)) {
      let base_consumeable_batch_size = Number(this.selectedResult['primary_pm_batch_size']) || Number(this.selectedResultbfr_results['primary_pm_batch_size']) || base_batch_size;
      
      for (let i = 0; i < this.consumeableMaterial.length; i++) {
        if (this.consumeableMaterial[i]['qty'] && base_consumeable_batch_size > 0) {
          // Calculate batch qty based on unit qty, overages, and batch size ratio
          let unit_qty = Number(this.consumeableMaterial[i]['qty']) || 0;
          let overages = Number(this.consumeableMaterial[i]['overages']) || 0;
          let overage_qty = (unit_qty * overages) / 100;
          let total_unit_qty = unit_qty + overage_qty;
          
          // Calculate batch quantity: (total_qty / base_batch_size) * new_batch_size
          let batch_qty = (total_unit_qty / base_consumeable_batch_size) * Number(batch_size);
          this.consumeableMaterial[i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
          this.consumeableMaterial[i]['batch_qty_unit'] = this.consumeableMaterial[i]['unit_name'] || 'Nos';
        }
      }
    }
  }

  // Calculate for Primary Packing Materials
  if (mat_type == 'PRIMARY_PM' || mat_type == 'ALL') {
    if (this.primary_pm_list && Array.isArray(this.primary_pm_list)) {
      let base_primary_pm_batch_size = Number(this.selectedResult['primary_pm_batch_size']) || Number(this.selectedResultbfr_results['primary_pm_batch_size']) || base_batch_size;
      
      for (let i = 0; i < this.primary_pm_list.length; i++) {
        if (this.primary_pm_list[i]['qty'] && base_primary_pm_batch_size > 0) {
          // Calculate batch qty based on unit qty, overages, and batch size ratio
          let unit_qty = Number(this.primary_pm_list[i]['qty']) || 0;
          let overages = Number(this.primary_pm_list[i]['overages']) || 0;
          let overage_qty = (unit_qty * overages) / 100;
          let total_unit_qty = unit_qty + overage_qty;
          
          // Calculate batch quantity: (total_qty / base_batch_size) * new_batch_size
          let batch_qty = (total_unit_qty / base_primary_pm_batch_size) * Number(batch_size);
          this.primary_pm_list[i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
          this.primary_pm_list[i]['batch_qty_unit'] = this.primary_pm_list[i]['unit_name'] || 'Nos';
        }
      }
    }
  }

  // Calculate for Secondary Packing Material
  if (mat_type == 'SECONDARY_PM' || mat_type == 'ALL') {
    if (this.packing_List_All && Array.isArray(this.packing_List_All)) {
      for (let j = 0; j < this.packing_List_All.length; j++) {
        if (this.packing_List_All[j]['packing_list'] && Array.isArray(this.packing_List_All[j]['packing_list'])) {
          let base_packing_batch_size = Number(this.packing_List_All[j]['batch_size']) || base_batch_size;
          
          for (let i = 0; i < this.packing_List_All[j]['packing_list'].length; i++) {
            if (this.packing_List_All[j]['packing_list'][i]['total_qty'] && base_packing_batch_size > 0) {
              // Calculate batch quantity: (total_qty / base_batch_size) * new_batch_size
              let batch_qty = (Number(this.packing_List_All[j]['packing_list'][i]['total_qty']) / base_packing_batch_size) * Number(batch_size);
              this.packing_List_All[j]['packing_list'][i]['batch_qty'] = parseFloat(batch_qty + '').toFixed(2);
            }
          }
        }
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

  // Ensure raw_materials is an array
  if (!this.raw_materials || !Array.isArray(this.raw_materials)) {
    this.raw_materials = [];
  }

  if (this.raw_materials.length == 0) {
    alertify.error('Raw Materials are required');
    return;
  }
  let temp = data.value;

  temp['mfr_no'] = this.selectedResultbfr_results['mfr_no']
  temp['product_code'] = this.selectedResultbfr_results['product_code']
  temp['raw_materials'] = this.raw_materials;
  temp['packing_materials'] = this.packing_List_All;
  temp['primary_pm_list'] = this.primary_pm_list;
  temp['consumeableMaterial'] = this.consumeableMaterial;
  temp['product_type'] = 'Raw Material';
  temp['rm_batch_size_unit'] = this.selectedResultbfr_results['bom_batch_size_unit'] ;
  this.service.post('planning/raw.php?type=save_batch_formulaZuma', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('Product Batch Formula has been saved successfully!');
      this.isView = false;
      this.isView = false;
  this.add_packing_material = false;
  this.prepareBF = false;
   this.isprepareBFView = false;
  this.prepare_batch = false;
    } else {
      alertify.error(response['status']);
     // alertify.error('Failed: An error occured, please try again!');
    }
  });
}
// searchQuery: string = '';

// get filteredResults(): any[] {
//   if (!this.searchQuery || this.searchQuery.trim() === '') {
//     return this.results; // Show all results when the search is empty
//   }

//   const query = this.searchQuery.toLowerCase().trim();

//   return this.results.filter((result) => {
//     return Object.values(result).some((value) =>
//       value?.toString().toLowerCase().includes(query)
//     );
//   });
// }

}
