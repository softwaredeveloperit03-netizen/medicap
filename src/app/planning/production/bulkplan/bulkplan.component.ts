import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-bulkplan',
  templateUrl: './bulkplan.component.html',
  styleUrls: ['./bulkplan.component.css']
})
export class BulkplanComponent implements OnInit {
  isClients = false;
  planned_qty = 0;
  batch_size = 0;
  lowest_batch = 0;
  can_plan_qty = 0;
  sale_qty = 0;
  ps_qty = 0;
  noof_batches = 0;
   results;
  dosages;
  batches;
  bfr_list;
  pack_list;
  mfr_list;
  clients;
  fg_sub_materials;
  selectedPO = [];
  sub_types;
  countries0;
  selectedProduct:any;
  selectedPachList = [];
  selectedResult = [];
  dosage_form = '';
  market_type = '';
  product_name = '';
  pack_multiple_countries = '';
  product_code;
  grade = '';
  names = '';
  has_sale_type = false;
  has_ps_type = false;
  is_both_types = false;
  grades;
  products;
  packlists;
  countries;
  packs;
  pack_sizes = [];
  materials = [];
  poList;
  number_of_batches = 0;
  mfr_batch_size = 0;
  bfr_batch_size = 0;
  unit_formula_id = 0;
  unit_formula_dtl_id=0;
  unitformula_pm_dtl = 0;
  selectedBatch = [];
  country_configuration = [];
  final_packing_materials_list = [];
  raw_materials;
  packing_materials;
  required_for = '';
  requirement = '';
  bom_no = '';
  bfr_no = ''
  plan_for_market = '';
  pack_size1 = '';
  country_specific = '';
  mfr_no = ''
  pack_size = ''
  pack_unit = '';
  units;
  isShortage = false;
  packsize;
  countries1: any;
  countries2: any;
  countries4: any;
  countries3: any;
  countriess;
  packing_type ='';

  Pack_Size= [];
  plant_id;
  Pack_Sizess;
  id: string;
  constructor(private service: DataAccessService, private router: Router) {


    if(this.router.getCurrentNavigation().extras.state?.plan_data) {
      console.log('sopData',this.selectedProduct);
      this.selectedProduct =  this.router.getCurrentNavigation().extras.state.plan_data;

      this.mfr_list = this.selectedProduct['mfr_records'];
      this.plan_for_market = this.selectedProduct['plan_for_market'];


    } else {
       this.router.navigate(['/planning/production/bulk_plan']);
    }


   }

  product_data:any;

  plan_type = 'Batch Wise';



  ngOnInit() {

    this.service.materialTypesChange();
    this.getSubMaterials();
    this.getClients();
    this.getUnits();
    this.plant_id = this.service.getPlantConfigFields("plant_id")



  }

  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }

  getProducts(dosage_form) {
    this.products = [];
    this.bfr_list = [];
    this.mfr_list = [];
    this.mfr_batch_size = 0;
    this.bfr_batch_size = 0;
    this.service.get('planning/raw.php?type=get_products_formulation_shortage_calculation&dosage_form=' + dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  idps;
  idps_unit;
  get_pack_size_by_mfrno(index){
    this.pack_list = [];
    this.countries1 = [];
    this.Pack_Size= [];
    // this.unit_formula_dtl_id=0;
     this.idps = this.Pack_Sizess[index-1]['pack_size'];
     this.idps_unit = this.Pack_Sizess[index-1]['unit'];
    this.service.get('planning/raw.php?type=get_pack_size_by_mfrno&unit_formula_dtl_id=' + this.pack_size1+'&bfr_no='+this.bfr_no).subscribe(response => {
      this.packs = response;
    });
    console.log(this.idps);
  }
  get_pack_size_by_mfrno1(index){
    this.pack_list = [];
    this.countries1 = [];
    // this.Pack_Size= [];
    // this.unit_formula_dtl_id=0;
     this.idps = this.Pack_Sizess[index-1]['id'];
     this.idps_unit = this.Pack_Sizess[index-1]['unit'];
    this.service.get('planning/raw.php?type=get_pack_size_by_mfrno&unit_formula_dtl_id=' + this.idps +'&bfr_no='+this.bfr_no).subscribe(response => {
      this.packs = response;
    });
    console.log(this.country_configuration);
  }




  getRawMaterialsByBfrNo(index) {
   this.bfr_batch_size=this.bfr_list[index-1]['batch_formula_weight']
    this.raw_materials = [];
    this.service.get('planning/raw.php?type=get_raw_materials_by_bfr_no&bfr_no=' + this.bfr_no).subscribe(response => {
      this.raw_materials = response['raw_materials'];
      this.countries = response['countries'];
    });
    this.number_of_batches = 0;

    this.getPackingMaterialsByBfrNo(0);
  }







  getPackingMaterialsByBfrNo(index) {
    this.pack_list = [];
    this.countries1 = [];
    this.service.get('planning/raw.php?type=get_packing_materials_by_mfr_id&bfr_no=' +
      this.bfr_no + '&unit_formula_id=' + this.unit_formula_id + '&market_type=' + this.plan_for_market).subscribe(response => {
        this.countriess = response;
        this.countries = response['pack_sizes'];
        this.countries2 = response['pack_sizes']['pack_size1'];
        this.countries1 = response['packing_materials'];
        this.Pack_Size = response['pack_size1'];
        this.Pack_Sizess = this.Pack_Size

        this.countries4 = response['pack_size1']['packing_materials'];
      });


  }



  getPackSizes(idx) {
    // this.pack_sizes = this.countries[idx - 1]['pack_size']
    this.pack_sizes = this.countries[idx - 1]['pack_size1']
    console.log(this.pack_sizes)

  }

  getProductGrades() {
    this.service.get('production.php?type=getProductGrades&dosage_form=' + this.dosage_form + '&product_name=' + this.product_name).subscribe(response => {
      this.grades = response;
    });
  }

  getPackList(index) {
    this.pack_list = []
    this.selectedPachList = []
    this.Pack_Sizess = this.Pack_Size
    console.log(this.Pack_Sizess);
    this.pack_list = this.countries0[index - 1]['packing_materials']
    this.unit_formula_dtl_id = this.Pack_Size[index - 1]['id'];


  }


  getBfrList(index) {
    this.bfr_list = []
    this.mfr_batch_size = this.mfr_list[index - 1]['batch_size'];
    this.bfr_list = this.mfr_list[index - 1]['bfr_records']
    this.bfr_batch_size = 0;
    this.unit_formula_id = this.mfr_list[index - 1]['id'];

  }


  deleteCountry(idx) {
    this.country_configuration.splice(idx, 1);
  }
  setpsQtyBatchSize() {
    if (Number(this.sale_qty) >= Number(this.bfr_batch_size)) {
      this.sale_qty = this.bfr_batch_size;
      this.ps_qty = 0;
    } else {
      this.ps_qty = Number(this.bfr_batch_size) - Number(this.sale_qty);
    }
  }
  generateCountryWiseBatches() {

  }
  getPlanQty() {
    this.isShortage = false;
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      material['plan_qty'] = (+material['batch_qty'] * +this.number_of_batches).toFixed(2);
      material['lod_qty'] = +parseFloat(((+material['plan_qty'] * +material['lod_per']) / 100) + '').toFixed(2);
      material['required_qty'] = +material['plan_qty'] + +material['lod_qty'];
      material['shortage_qty'] = +material['required_qty'] - +material['available_qty'];
      if (+material['shortage_qty'] < 0) {
        material['shortage_qty'] = 0;
      } else {
        this.isShortage = true;
      }
      this.materials[i] = material;
    }
  }
  sendIndend() {
    let temp = {};
    temp['material'] = this.materials;
    temp['requirement'] = this.requirement;
    temp['required_for'] = this.required_for;

    this.service.post('production/shortage.php?type=sendIndend', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        // this.isNewForm=false;
        alertify.success('Indend Send successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
  multi_packing;

  groupedMaterials =[];



  calculateBatchQty() {
    this.planned_qty = 0;
    // /number_of_batches
    let min_batch = [];
    this.groupedMaterials =[];
    for (let i = 0; i < this.raw_materials.length; i++) {

        let qty = Number(this.raw_materials[i]['batch_qty'] * Number(this.number_of_batches));
        this.raw_materials[i]['plan_qty'] = parseFloat(qty + '').toFixed(2);


      let can_plan_batches = Number(this.raw_materials[i]['avbl_stock']) / Number(this.raw_materials[i]['batch_qty']);
      let minBatch = Math.round(can_plan_batches);
      this.raw_materials[i]['can_plan_batches'] = minBatch;
      this.raw_materials[i]['shortage_qty'] = (Number(this.raw_materials[i]['avbl_stock']) - Number(this.raw_materials[i]['plan_qty'])).toFixed(2);


      min_batch.push(minBatch);
      // console.log(minBatch);
      // console.log(  this.raw_materials[i]['can_plan_batches']);
      // console.log(  this.raw_materials['can_plan_batches']);


    }
    let temp = {}; // Initialize the 'temp' object if not already defined
    temp['packing_materials'] = [];
    for (let i = 0; i < this.packs.length; i++) {

      // let batch_qty = Number(this.packs[i]['batch_formula_weight']) * Number(this.packs[i]['total_qty']);

      // this.packs[i]['batch_qtyqqqq'] = (Number(this.packs[i]['batch_formula_weight']) * Number(this.packs[i]['total_qty'])) / Number(this.packs[i]['batch_size']);



      if( this.multi_packing=='No'){
      if(this.plant_id==67){
        let idps=0;
        if(this.idps_unit=='gm'){
           idps=Number(this.idps/1000);
           console.log('idps='+idps);
        }
        let qty0 = Number(this.bfr_batch_size)/Number(idps);
        console.log('qty0='+qty0);
        let qty= Number(qty0) * Number(this.packs[i]['total_qty'])
        console.log('total_qty+'+this.packs[i]['total_qty'])
        console.log('qty='+qty);
        this.packs[i]['plan'] = parseFloat(qty + '').toFixed(2);
        this.packs[i]['batch_qtyqqqq'] = (Number(this.packs[i]['batch_formula_weight']) * Number(this.packs[i]['total_qty'])) / Number(this.packs[i]['batch_size']);

      }else{
        this.packs[i]['batch_qtyqqqq'] = (Number(this.packs[i]['batch_formula_weight']) * Number(this.packs[i]['total_qty'])) / Number(this.packs[i]['batch_size']);

        this.packs[i]['plan'] = (Number(this.packs[i]['batch_qtyqqqq']) * Number(this.number_of_batches)).toFixed(2);
      }
     }
      else if( this.pack_multiple_countries=='No'){

        let idps=0;
        if(this.idps_unit=='gm'){
           idps=Number(this.idps/1000);
           console.log('idps='+idps);
        }else{
          idps=Number(this.idps);
          console.log('idps='+idps);
        }

        let qty0 = Number(this.bfr_batch_size)/Number(idps);
        console.log('qty0='+qty0);
        let qty= Number(qty0) * Number(this.packs[i]['total_qty'])
        console.log('total_qty+'+this.packs[i]['total_qty'])
        console.log('qty='+qty);
        this.packs[i]['plan'] = parseFloat(qty + '').toFixed(2);
        this.packs[i]['batch_qtyqqqq'] = (Number(this.packs[i]['batch_formula_weight']) * Number(this.packs[i]['total_qty'])) / Number(this.packs[i]['batch_size']);


     }



     else{

      for (let i = 0; i < this.country_configuration.length; i++) {

        const packs=this.country_configuration[i];

        for (let j = 0; j < packs['packs'].length; j++) {


          let idps = 0;

          if (this.country_configuration[i].idps_unit === 'gm') {

            idps = Number(this.country_configuration[i].packs1234 / 1000);
            console.log('idps=' + idps);
          } else {
            idps = Number(this.country_configuration[i].packs1234);
            console.log('idps=' + idps);

          }

          let qty0 = Number(this.country_configuration[i]['sale_qty']) / idps;
          console.log('sale_qty=' + this.country_configuration[i]['sale_qty']);
          console.log('qty0=' + qty0);

          let qty = qty0 * Number(packs['packs'][j]['total_qty']);
          console.log('total_qty=' + packs['packs'][j]['total_qty']);
          console.log('qty=' + qty);

          packs['packs'][j]['plan'] = parseFloat(qty + '').toFixed(2);
          console.log('plan=' + packs['packs'][j]['plan']);
          packs['packs'][j]['batch_qtyqqqq'] = (Number(this.country_configuration[i]['sale_qty']) * Number(packs['packs'][j]['total_qty'])) / Number(packs['packs'][j]['batch_size']);
console.log(packs['packs'][j]['batch_formula_weight']);
console.log('batch_qtyqqqq='+packs['packs'][j]['batch_qtyqqqq']);
        }
      }
     }

       this.packs[i]['can_plan'] = Number(this.packs[i]['batch_qtyqqqq']) * Number(this.lowest_batch);
      this.packs[i]['pm_overages'] = Number(this.packs[i]['pm_overages']) ;
        const balanceQty = Number(this.packs[i]['balance_qty']);
        const planQty = Number(this.packs[i]['plan']);
        const shortQty = balanceQty - planQty;
        this.packs[i]['short_qt'] = shortQty;

      temp['packing_materials'].push(this.packs[i]);

    }
    const min = Math.min(...min_batch)
     this.lowest_batch = min < 0 ? 0 : min;
    this.can_plan_qty = min < 0 ? 0 : min * this.bfr_batch_size;
     if (this.plan_type == 'Batch Wise') {

      this.planned_qty = this.bfr_batch_size * this.number_of_batches;
      console.log(this.planned_qty);
      console.log(this.bfr_batch_size);
      console.log(this.number_of_batches);
    }
    for (let k = 0; k < this.final_packing_materials_list.length; k++) {
      for (let i = 0; i < this.final_packing_materials_list[k]['packing_materials'].length; i++) {
        let qty = Number( this.final_packing_materials_list[k]['packing_materials'][i]['batch_qty'] * Number(this.number_of_batches));
        this.final_packing_materials_list[k]['packing_materials'][i]['plan_qty'] = parseFloat(qty + '').toFixed(2);
        let can_plan_batches = Number( this.final_packing_materials_list[k]['packing_materials'][i]['avbl_stock']) / Number( this.final_packing_materials_list[k]['packing_materials'][i]['batch_qty']);
        this.final_packing_materials_list[k]['packing_materials'][i]['can_plan_batches'] = Math.round(can_plan_batches);
        this.final_packing_materials_list[k]['packing_materials'][i]['shortage_qty'] = (Number( this.final_packing_materials_list[k]['packing_materials'][i]['avbl_stock']) - Number( this.final_packing_materials_list[k]['packing_materials'][i]['plan_qty'])).toFixed(2);
      }
    }




    this.groupedMaterials = this.raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});







  }
  getPackinSizesByCountry(idx) {
    this.has_sale_type = false;
    this.has_ps_type = false;
    this.pack_sizes = this.countries[idx - 1]['pack_size'];
    // for (let x = 0; x < this.pack_sizes.length; x++) {
    //   if (this.pack_sizes['packing_type'] == 'Sale') {
    //     this.has_sale_type = true;
    //   }
    //   if (this.pack_sizes['packing_type'] == 'Sample(PS)') {
    //     this.has_ps_type = true;
    //   }
    // }
  }
  setBatchQty(idx) {
    this.is_both_types = false;
    if ((idx-1) == 0) {
      this.sale_qty = this.bfr_batch_size;
      this.ps_qty = 0;
    }
    else if ((idx-1) == 1) {
      this.sale_qty = 0;
      this.ps_qty = this.bfr_batch_size;
    }
    else {
      this.is_both_types = true;
      this.sale_qty = 0;
      this.ps_qty = 0;
    }

  }
  getPackingMaterial(idx) {
    this.packing_materials = [];
    this.pack_size = this.pack_sizes[idx - 1]['pack_size'];
    this.pack_unit = this.pack_sizes[idx - 1]['unit'];
    this.packing_materials = this.pack_sizes[idx - 1]['packing_materials'];
    let en = this.packing_materials.length;
  }
  packs_data=[];

  addCountry(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
     temp['packs1234'] = this.idps;
     if (temp['ps_qty'] === null || temp['ps_qty'] === undefined) {
      temp['ps_qty'] = 'NA';
    }
     if (temp['sale_qty'] === null || temp['sale_qty'] === undefined) {
      temp['sale_qty'] = 'NA';
    }

    console.log(temp);


  //
    // Create a separate temp1 object
    let temp1 = {};
    temp1['packs'] = this.packs;
    temp1['packs1234'] = this.idps;
    temp1['idps_unit'] = this.idps_unit;


    // Merge the properties of temp and temp1 into a new object
    let combinedObject = { ...temp, ...temp1 };

    // Include combinedObject in the country_configuration array
    this.country_configuration.push(combinedObject);

    // Optionally, you can print this.country_configuration if needed
    console.log('country_configuration:', this.country_configuration);

    // Combine all 'packs' arrays into a single array
    let combinedPacks = this.country_configuration.reduce((accumulator, currentValue) => {
      if (currentValue.packs) {
        return accumulator.concat(currentValue.packs);
      }
      return accumulator;
    }, []);

    // Print the combined 'packs' array
    console.log('combined packs:', combinedPacks);

    // Update this.packs with the combinedPacks array if needed
    this.packs = combinedPacks;

    data.resetForm();
  }




  adddata(data) {
    if (!data.valid) {
      alertify.error('All Fields are Mandatory');
      return;
    }
    let temp = data.value;


    if (temp['packing_type'] == 'Both') {
      var itemIndex = this.pack_sizes.findIndex(x => x.packing_type == "Sale");
      var itemIndex1 = this.pack_sizes.findIndex(x => x.packing_type == "Sample(PS)");
      if (itemIndex == -1 && itemIndex1 == 0) {
        alertify.error('Packing Sale has no Packing Materials');
        return;
      }
      if (itemIndex1 == -1 && itemIndex == 0) {
        alertify.error('Packing Sample (PS) has no Packing Materials');
        return;
      }

      let sale_materials = this.pack_sizes[itemIndex]['packing_materials'];
      let ps_sale_materials = this.pack_sizes[itemIndex1]['packing_materials'];



      let pack_batch_size = this.pack_sizes[itemIndex]['batch_size'];
      for (let i = 0; i < sale_materials.length; i++) {
        var item = sale_materials[i];
        let batchQty = ((Number(item['total_qty']) * Number(temp['sale_qty']) / Number(pack_batch_size)).toFixed(2));
        sale_materials[i]['batch_qty'] = Number(batchQty).toFixed(2);
      }
      let obj = {
        'index': this.country_configuration.length,
        'country_name': temp['country_name'],
        'pack_size' : this.pack_sizes[itemIndex]['pack_size'],
        'pack_size_unit' : this.pack_sizes[itemIndex]['unit'],
        'pack_batch_size' : this.pack_sizes[itemIndex]['batch_size'],
        'pack_type': 'Sale',
        'batch_size': temp['sale_qty'],
        'packing_materials': sale_materials
      }
      this.final_packing_materials_list.push(obj);


      pack_batch_size = this.pack_sizes[itemIndex1]['batch_size'];
      for (let i = 0; i < ps_sale_materials.length; i++) {
        var item = ps_sale_materials[i];
        let batchQty = ((Number(item['total_qty']) * Number(temp['ps_qty'])) / Number(pack_batch_size)).toFixed(2);
        ps_sale_materials[i]['batch_qty'] = Number(batchQty).toFixed(2);
      }
      let obj1 = {
        'index': this.country_configuration.length,
        'country_name': temp['country_name'],
        'pack_size' : this.pack_sizes[itemIndex1]['pack_size'],
        'pack_size_unit' : this.pack_sizes[itemIndex1]['unit'],
        'pack_batch_size' : this.pack_sizes[itemIndex1]['batch_size'],
        'pack_type': 'Sample(PS)',
        'batch_size': temp['ps_qty'],
        'packing_materials': ps_sale_materials
      }
      this.final_packing_materials_list.push(obj1);

    } else if (temp['packing_type'] == 'Sale') {
      var itemIndex = this.pack_sizes.findIndex(x => x.packing_type == "Sale");
      if (itemIndex == -1) {
        alertify.error('Packing Sample (PS) has no Packing Materials');
        return;
      }
      let sale_materials = this.pack_sizes[itemIndex]['packing_materials'];
      let pack_batch_size = this.pack_sizes[itemIndex]['batch_size'];
      for (let i = 0; i < sale_materials.length; i++) {
        var item = sale_materials[i];
        let batchQty = ((Number(item['total_qty']) * Number(temp['sale_qty']) / Number(pack_batch_size)).toFixed(2));
        sale_materials[i]['batch_qty'] = Number(batchQty).toFixed(2);
      }
      let obj = {
        'index': this.country_configuration.length,
        'country_name': temp['country_name'],
        'pack_size' : this.pack_sizes[itemIndex]['pack_size'],
        'pack_size_unit' : this.pack_sizes[itemIndex]['unit'],
        'pack_batch_size' : this.pack_sizes[itemIndex]['batch_size'],
        'pack_type': 'Sale',
        'batch_size': temp['sale_qty'],
        'packing_materials': sale_materials
      }
      this.final_packing_materials_list.push(obj);

    } else {
      var itemIndex = this.pack_sizes.findIndex(x => x.packing_type == "Sample(PS)");
      let ps_sale_materials = this.pack_sizes[itemIndex]['packing_materials'];

      if (itemIndex == -1) {
        alertify.error('Packing Sale has no Packing Materials');
        return;
      }
      var pack_batch_size = this.pack_sizes[itemIndex]['batch_size'];
      for (let i = 0; i < ps_sale_materials.length; i++) {
        var item = ps_sale_materials[i];
        let batchQty = ((Number(item['total_qty']) * Number(temp['ps_qty'])) / Number(pack_batch_size)).toFixed(2);
        ps_sale_materials[i]['batch_qty'] = Number(batchQty).toFixed(2);
      }
      let obj1 = {
        'index': this.country_configuration.length,
        'country_name': temp['country_name'],
        'pack_size' : this.pack_sizes[itemIndex]['pack_size'],
        'pack_size_unit' : this.pack_sizes[itemIndex]['unit'],
        'pack_batch_size' : this.pack_sizes[itemIndex]['batch_size'],
        'pack_type': 'Sample(PS)',
        'batch_size': temp['ps_qty'],
        'packing_materials': ps_sale_materials
      }
      this.final_packing_materials_list.push(obj1);
    }

    this.country_configuration.push(temp);





    data.resetForm();
  }

  setNoOfBatches() {
    if (this.plan_type != 'Total Qty Wise') {
      return;
    }
    let bqty = Number(this.planned_qty) / Number(this.bfr_batch_size)
    if (Number(bqty) > 0) {
      let round = Math.round(bqty);
      this.number_of_batches = round;
      this.calculateBatchQty();
    }
  }
  saveBatchPlan(data, based_on) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['batch_size'] = this.bfr_batch_size;
    temp['pack_unit'] = this.pack_unit;
    temp['plan_based_on'] = based_on;
    // temp['bfr_no'] = this.selectedResult['bfr_no'];
    temp['raw_materials'] = this.raw_materials;
    temp['packing_materials'] = this.packs;

    console.log(temp);
    this.service.post('planning/raw.php?type=save_plan', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alertify.success('Saved Successfully');
        this.router.navigate(['/planning/production']);
      } else {
        alertify.error('An error occured, Please try again');
      }
    });
  }
  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });

    console.log(this.clients);
  }
  checkRequiredfor(value) {
    if (value !== 'Own') {
      this.getClients();
      this.isClients = true;
    } else {
      this.isClients = false;
    }
  }

  getClientPos(client_code) {
    this.service.get('production/plan.php?type=get_client_po_by_prod_code&product_code=' + this.selectedProduct['product_code'] + '&client_code=' + client_code).subscribe(response => {
      this.poList = response;
    });
   }

  getPoDetails(idx) {
    this.selectedPO = this.poList[idx - 1];
    console.log(this.selectedPO);
  }
  getSubMaterials() {
    this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
      this.fg_sub_materials = response;
    });

  }



}
