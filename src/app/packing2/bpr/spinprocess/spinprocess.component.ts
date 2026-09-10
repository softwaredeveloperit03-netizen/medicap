import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-spinprocess',
  templateUrl: './spinprocess.component.html',
  styleUrls: ['./spinprocess.component.css']
})
export class SpinprocessComponent implements OnInit {

  
  clicked = false;
  
  
  from_time;
  to_time;
  start_time;
  end_time;
  isTime = false;
  isView = false;
  show_Iqpc_Test = false;
  results;
  selectedIndex = -1;
  selectedResult = [];
  selectedStage = [];
  units;
  selectedPage = 3;
  isNewIncident = false;
  bmr_lots = [];
  bmr_common_lots = [];
  bmr_common_lotsss = [];
  equipments;
  isNewMaintenance = false;
  isNewDeviation = false;
  isPowerFailure = false;
  isEnvironmentCheck = false;
  isEquipmentUsage = false;
  isYieldStatement = false;
  yield_unit = '';
  actual_yieldwt = 0;
  selectedDispensing = [];
  isViewshow = false;
  selectedContainer = [];
  selectedMaterial = [];
  isNewTI = false;
  equip;
  equipment_id = '';
  bmr_checllist = [];
  bmr_info;
  selectedEquip = [];
  isTechnicalInfo = false;
  selectedSpecification = [];
  specificationTest = [];
  specTest = [];
  isNewshowProcessForm = false;
  isShowYeild = false;
  isNewshowIqpc = false;
  selected_ipqc = null;
  operators;
  _product_code = '';
  _index = 0;
  _lot_no = 0;
  batch_yeild_min = 0;
  total_qty = 0;
  batch_yeild_max = 0;
  bath_commencent_date = '';
  bath_complete_date = '';
  actual_yeild = 0;
  yeild_percent = '0';
  total_yeild :any;
  total_days: number;
  stage_yield:number=0;
  expected_yield:number=0;
  pack_material_losss;
  unit_packed;
  constructor(public service: DataAccessService, private router: Router) {
    // this.bath_commencent_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    // this.bath_complete_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
  
  ngOnInit() {
    this.getInprocessBatches();
    // this.getEquipments();
    this.getOperator();
   
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  
     this.GET_quality_sample_cheklist();
  }


  selectedFile2: File;


  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  Maddgen(data){

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
  
      uploadData.append(key, value);
    } 
    if (this.selectedFile2 !== undefined) {
      uploadData.append('label', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('production/product.php?type=save_prod_label&id='+this.selected_sifter['id'] , uploadData).subscribe(response => {
      if (response['status'] === 'success') {
      alertify.success('Successfull');
      data.resetForm();
      this.isView = false;
      this.isShow = true;
      this.get_int_sift()
     
 
 
    } else {
      alertify.error('Failed: An error occured, Please try again!');
    }
  });

  }
  delLabel(){


    this.service.get('production/product.php?type=del_prod_label&id='+this.selected_sifter['id'] ).subscribe(response => {
      if (response['status'] === 'success') {
      alertify.success('Successfull');
       this.isView = false;
      this.isShow = true;
      this.get_int_sift()
     
 
 
    } else {
      alertify.error('Failed: An error occured, Please try again!');
    }
  });

  }



  saveoprpccp(data,data1){    
    let temp = data.value;
   
    
    this.service.post('bmr/process.php?type=saveoprpccp2&section=1&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
         this.getCCP2();
         this.getCCP2_s2();
         
        data.reset();
        temp.length=0;
       
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveoprpccp_s2(data,data1){    
    let temp = data.value;
    this.service.post('bmr/process.php?type=saveoprpccp2&section=2&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
         this.getCCP2_s2();
        data.reset();
        temp.length=0;
       
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  // saveoprpccp(data){
  //   if (!data.value) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   temp['oprps'] = this.oprps;
  //   this.service.post('production/product.php?type=saveoprp111 &id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') { 
  //       alert('Saved Successfully');
  //       this.router.navigate(['/packing/bpr']);
  //       this.getInprocessBatches();
  //        this.isView = false;
  //       data.reset();
        
  //       } else {
  //       alert('Failed: An error occured, please try again!');
  //     }
  //   });
  // }
  oprps
  oprp2
  getCCP2(){
    this.service.get('bmr/process.php?type=GET_oprp_chek2&section=1&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id']).subscribe(response=>{
      this.oprps = response;
    
    });
  }
  oprps_s2;
  getCCP2_s2(){
    this.service.get('bmr/process.php?type=GET_oprp_chek2&section=2&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id']).subscribe(response=>{
      this.oprps_s2 = response;
      // this.oprp2=this.oprps['oprpccp_details'][0]
      // console.log(this.oprp2);
    });
  }
  processes;
  delete_oprp_ccp2(id) {
    console.log(id)
    this.service.post('bmr/process.php?type=delete_oprp_ccp2&id='+id, JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Delete Successfully');
         this.getCCP2();
            } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  delete_oprp_ccp2_s2(id) {
    console.log(id)
    this.service.post('bmr/process.php?type=delete_oprp_ccp2&id='+id, JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Delete Successfully');
         this.getCCP2_s2();
            } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  check_bmr(){

    let temp= {};
    // temp['pk_equip_list'] = this.pk_equip_list;
    this.service.post('packing/process.php?type=send_bmr_check&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.getInprocessBatches();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }


  keyFunc(value){
    this.pack_material_losss= (this.unit_packed / 100)* value;
    console.log(this.pack_material_losss)
  }
  keyFunc2(value){
    this.pack_material_losss= (this.unit_packed / 100)* value;
    console.log(this.pack_material_losss)
  }

  save_pk_eq(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    temp['pk_equip_list'] = this.pk_equip_list;
    this.service.post('packing/process.php?type=SAVE_bmr_Equipment_data&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.reset();
       
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  packing_type;
Filling_qty;
  save_prim_pk_form(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    temp['primary_pk'] = this.primary_pk;
    temp['packing_type'] = this.packing_type;
    temp['Filling_qty'] = this.Filling_qty;

    this.service.post('packing/process.php?type=SAVE_primary_packing_section&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  Filling_qty2;
packing_type2;
  save_sec_pk_form(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    temp['secondary_pk'] = this.secondary_pk;
    temp['packing_type'] = this.packing_type2;
    temp['Filling_qty'] = this.Filling_qty2;

    this.service.post('packing/process.php?type=SAVE_secondary_packing_section&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.reset();
        this.secondary_pk=[];
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  qchecks;
  GET_quality_sample_cheklist(){
    this.service.get('bmr/process.php?type=GET_quality_sample_cheklist').subscribe(response=>{
      this.qchecks = response;

    });
  }
  savepacking_quality_sample_check(data){
  
    let temp = data.value;
    temp['sample_checks'] = this.qchecks;


    this.service.post('packing/process.php?type=savepacking_quality_sample_check&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.reset();
        this.sample_checks=[];
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  deviation;
defect_observed;
units_checked;
  save_finale(data){
  
    let temp = data.value;
    temp['deviation'] = this.deviation;
    temp['defect_observed'] = this.defect_observed;
    temp['units_checked'] = this.units_checked;


    this.service.post('packing/process.php?type=savepacking_Final_quality_sample_check&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.reset();
        this.sample_checks=[];
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  deleteEquipments(index){
    this.pk_equip_list.splice(index, 1);
  }
  
  getInprocessBatches() {
    //this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Production_Activity_Formulation&material_type=Raw Material').subscribe(response => {
    this.service.get('production/product.php?type=getReadyBatchPlans_pk_sp&material_type=Packing Material').subscribe(response => {
      this.results = response;
      this.results = response;
      if (this.selectedIndex !== -1 && this.results?.length > 0) {
        this.view(this.selectedIndex);
      } else {
        this.isView = false;
      }
    });
  }
  send_Intimation(){
    this.service.post('packing/sampling.php?type=sendIntimation_saipro&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'],JSON.stringify(this.selectedResult)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('send Intimation Successfuly');
       
      }else{
        alertify.error('Some error Ocuured!');
      }

    });
  }
  ti_sheet;
  eq_usage;
  getBmrCheckList(product_code, index) {
   
    var stage_wise_lot_data = [];
    this.bmr_lots = [];
    this.bmr_checllist = []
  
    this.bmr_common_lots = [];
    this.bmr_common_lotsss = [];
    var no_of_lots: number = 0
    this._product_code = product_code;
    this._index = index;
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.batch_yeild_min = this.selectedResult['min_yeild'];
    this.total_qty = this.selectedResult['total_qty'];
 
    this.batch_yeild_max = this.selectedResult['max_yeild'];
    this.service.get('store/dispensing.php?type=get_bmr_checklist_by_product_Code_pk&product_code=' + product_code).subscribe(response => {
      this.bmr_info = response;
      no_of_lots = this.selectedResult['no_of_lots'];
 
      var temp_array = [];
      for (var i = 0; i < no_of_lots; i++) {
  
        for (var j = 0; j < this.bmr_checllist.length; j++) {
          if (this.bmr_checllist[j]['split_into_lots'] == 'Yes') {
            temp_array.push(this.bmr_checllist[j])
          }
        }
        let obj = {
          "lot_no": i + 1,
          "lots": temp_array
        };
        this.bmr_lots.push(obj);
        temp_array = [];
  
      }
  
      var temp_array = [];
  
  
      for (var j = 0; j < this.bmr_checllist.length; j++) {
        if (this.bmr_checllist[j]['split_into_lots'] == 'No') {
          this.bmr_common_lots.push(this.bmr_checllist[j])
        }
      }
      for (var j = 0; j < this.bmr_checllist.length; j++) {
        if (this.bmr_checllist[j]['fg_sampling'] == 'yes') {
          this.bmr_common_lotsss.push(this.bmr_checllist[j])
        }
      }
  
  
      for (var i = 0; i < stage_wise_lot_data.length; i++) {
        var stage_dtl_id: number = +stage_wise_lot_data[i]['stage_dtl_id'];
        var b_lot_no = +stage_wise_lot_data[i]['lot_no'];
        for (var j = 0; j < this.bmr_lots.length; j++) {
          var lotNo = this.bmr_lots[j]['lot_no']
          if (b_lot_no != lotNo) {
            continue;
          }
          var itemIndex = this.bmr_lots[j]['lots'].findIndex(x => x.id == stage_dtl_id);
          if (itemIndex != null && itemIndex != undefined) {
            this.bmr_lots[j]['lots'][itemIndex] = { ... this.bmr_lots[j]['lots'][itemIndex], batch_stage_id: stage_wise_lot_data[i]['batch_stage_id'] };
            this.bmr_lots[j]['lots'][itemIndex] = { ... this.bmr_lots[j]['lots'][itemIndex], remarks_entry_by: stage_wise_lot_data[i]['remarks_entry_by'] };
            this.bmr_lots[j]['lots'][itemIndex] = { ... this.bmr_lots[j]['lots'][itemIndex], process_entry_by: stage_wise_lot_data[i]['process_entry_by'] };
            this.bmr_lots[j]['lots'][itemIndex] = { ... this.bmr_lots[j]['lots'][itemIndex], yeild_entry_by: stage_wise_lot_data[i]['yeild_entry_by'] };
            this.bmr_lots[j]['lots'][itemIndex] = { ... this.bmr_lots[j]['lots'][itemIndex], ipqc_status: stage_wise_lot_data[i]['test_result_status'] };
            this.bmr_lots[j]['lots'][itemIndex] = { ... this.bmr_lots[j]['lots'][itemIndex], test_result: stage_wise_lot_data[i]['test_result'] };
          }
          // for (var k = 0; k < this.bmr_lots[j]['lots'].length; k++) {
          //   let id = +this.bmr_lots[j]['lots'][k]['id'];
  
          //   if (stage_dtl_id == id) {
          //     console.log(stage_dtl_id);
          //     if (b_lot_no == lotNo) {
          //       console.log(b_lot_no);
          //       this.bmr_lots[j]['lots'][k]['remarks_entry_by'] = stage_wise_lot_data[i]['remarks_entry_by'];
          //       this.bmr_lots[j]['lots'][k]['process_entry_by'] = stage_wise_lot_data[i]['process_entry_by'];
          //       this.bmr_lots[j]['lots'][k]['yeild_entry_by'] = stage_wise_lot_data[i]['yeild_entry_by'];
          //     }
          //   }
          // }
        }
      }
      //   console.log(JSON.stringify(this.bmr_lots));
  
      this.isShow = true;
      this.get_int_sift();
    }); 
  }


  ViewLabel(url) {
    url = this.service.url + '../../upload/production_label/' + url;
    
    window.open(url, '_blank');
  }


int_sifters;
isShow = false;  
get_int_sift() {
  this.service.get('production/product.php?type=get_savebmr_sift_pk_inprocess_pk&work_order_no='+this.selectedResult['work_order_no']+'&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_id='+this.selectedResult['a_id']).subscribe(response => {
    this.int_sifters = response;
  });
}
label;
selected_sifter=[];
add(index){
  this.selected_sifter=this.int_sifters[index]  
  this.label=this.selected_sifter['label']
  this.selectedMaterial = this.selected_sifter['materials'];
  this.isView = true;
  this.isShow = false;  
}

  view(index) {
  
     this.selectedStage = this.selectedResult['stage'];
  
      this.isView = true;
  }

  showYeildForm(idx, lot_idx) {
    this.selected_ipqc = this.bmr_lots[lot_idx]['lots'][idx];
    this._lot_no = this.bmr_lots[lot_idx]['lot_no'];
    this.isShowYeild = true;
  }
  showProcessForm(idx, lot_idx) {
    this.selected_ipqc = this.bmr_lots[lot_idx]['lots'][idx];
    this._lot_no = this.bmr_lots[lot_idx]['lot_no'];
    this.selected_ipqc['range'] = this.selected_ipqc['lower_limit'] + '-' + this.selected_ipqc['upper_limit'] + ' ' + this.selected_ipqc['unit'];
    this.isNewshowProcessForm = true;
  }
  showIqpcTest(idx, lot_idx) {
    this.selected_ipqc = this.bmr_lots[lot_idx]['lots'][idx];
    this._lot_no = this.bmr_lots[lot_idx]['lot_no'];
    if (this.selected_ipqc['result_type'] == 'Range') {
      this.selected_ipqc['range'] = this.selected_ipqc['lower_limit'] + '-' + this.selected_ipqc['upper_limit'] + ' ' + this.selected_ipqc['unit'];
    } else if (this.selected_ipqc['result_type'] == 'Not Less Than') {
      this.selected_ipqc['range'] = '< ' + this.selected_ipqc['less_than_value'];
    } else if (this.selected_ipqc['result_type'] == 'Not More Than') {
      this.selected_ipqc['range'] = '>' + this.selected_ipqc['more_than_value'];
    } else {
      this.selected_ipqc['range'] = this.selected_ipqc['result_type'];
    }
     this.show_Iqpc_Test = true;
    // this.isNewTI= true;
  }
  saveBmrData(data) {
  
  }
  // showProcessForm(i){
  //   this.selected_ipqc = this.bmr_checllist[i];
  //   this.selected_ipqc['range'] = this.selected_ipqc['lower_limit'] + '-' + this.selected_ipqc['upper_limit'] + ' ' + this.selected_ipqc['unit'];
  //   this.show_Iqpc_Test = true;
  // }
  
  viewshow(index) {
    let material = this.selectedDispensing['materials'];
    this.selectedMaterial = material[index];
    this.selectedContainer = this.selectedMaterial['containers'];
    console.log(this.selectedContainer);
    this.isViewshow = true;
  }
  
  
  selectPage(index) {
    this.selectedPage = index;
    this.get_disp_material();
    this.getequipments();
    this.getCCP2();
    this.getCCP2_s2();
  }
  
  tisheet() {
    this.isNewTI = true;
  }
  

  pkequipments
  getequipments(){
    this.service.get('equipments.php?type=getEquipmentNames&department1=Packing').subscribe(response =>{
      this.pkequipments = response;
    });
  }
  pk_equip_list=[];
  selectedEquipment_data=[]
  selectEquipment_data(index) {
    this.selectedEquipment_data = this.pkequipments[index-1];
console.log(this.selectedEquipment_data);
}
selectedEquipment=[]
  addEquipment_data(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
 

    let temp={};
    temp['equipment_code']=this.selectedEquipment_data['equipment_code'];
    temp['category']=this.selectedEquipment_data['category'];
    temp['capacity']=this.selectedEquipment_data['capacity'];
    temp['equipment_name']=this.selectedEquipment_data['equipment_name'];
   
    this.pk_equip_list[this.pk_equip_list.length] = temp;
    console.log(this.pk_equip_list);
    data.resetForm();
  }
  primary_pk=[];
  add_pack_data(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
    this.primary_pk[this.primary_pk.length] = temp;
    console.log(this.pk_equip_list);
    console.log(this.primary_pk);
    data.resetForm();
  }
  secondary_pk=[];
  add_pack_data2(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    let tempData = [];

    
   
    this.secondary_pk[this.secondary_pk.length] = temp;

    data.resetForm();
  }
  sample_checks=[];
  add_sample_check(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    let tempData = [];

    
   
    this.sample_checks[this.sample_checks.length] = temp;

    data.resetForm();
  }
  disp_materials;
  get_disp_material() {
    this.service.get('packing/process.php?type=getDisp_material&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_order_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id']).subscribe(response => {
      this.disp_materials = response;
    });
  }
  
  getEquipmentsbycode(index) {
    this.selectedEquip = this.equip[index];
    console.log('ff', this.selectedEquip);
  }
  
  
  start() {
    this.service.get('production/manufacturing.php?type=startStage&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&stage=' + this.selectedResult['current_stage']).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  callforclearance() {
    this.service.post('production/manufacturing.php?type=callforclearance&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'], JSON.stringify(this.selectedStage['clearances'])).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  saveEnvironmentCheck() {
    this.service.post('production/manufacturing.php?type=saveEnvironmentCheck&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'], JSON.stringify(this.selectedStage['environments'])).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  send(data) {
    let temp = data.value;
    temp['product_code'] = this.selectedResult["product_code"];
    temp["stage"] = this.selectedResult["current_stage"];
    temp["batch_no"] = this.selectedResult["batch_no"];
    this.service.post('production/technical.php?type=saveTISheet', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success("data save successfuly");
        data.resetForm();
        this.isNewTI = false;
        this.getInprocessBatches();
      } else {
        alertify.error('Some error Occured');
      }
    });
  }
  calculate_diff_days() {
    var date1 = new Date(this.bath_commencent_date);
    var date2 = new Date(this.bath_complete_date);
    if (date2.getTime() < date1.getTime()) {
      alertify.error('Batch Complete date should be greater than Batch Commencement date');
      this.total_days =0;
      return;
    }
    var Time = date2.getTime() - date1.getTime();
    this.total_days = Time / (1000 * 3600 * 24);
  }
  saveInprocessCheck() {
    this.service.post('production/manufacturing.php?type=saveInprocessCheck&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'], JSON.stringify(this.selectedStage['checks'])).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.error(response['msg']);
      }
    });
  }
  
  saveWeightCheck() {
    this.service.post('production/manufacturing.php?type=saveWeightCheck&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'], JSON.stringify(this.selectedStage['weighings'])).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  equipment_ids: any = [];
  getEquipmentIDs(index) {
    index = index - 1;
    let equipments = this.selectedStage['equipments'];
    let temp = equipments[index];
    this.equipment_ids = temp['equipments'];
  }
  
  startEquipmentUsage(data) {
    if (!data.valid) {
      alertify.error('Equipment Name & Code is required!');
      return;
    }
    this.service.post('production/manufacturing.php?type=startEquipmentUsage&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'], JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  stopEquipmentUsage(id) {
    this.service.get('production/manufacturing.php?type=stopEquipmentUsage&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  transferStock_fg(a) {   
    let temp =a
    this.service.post('production/manufacturing.php?type=transfer_workorder_pk_dept_sampling&id='+this.selectedResult['id'],JSON.stringify(temp) ).subscribe(response => {
      if (response['status'] == 'success') {
     
        this.isView= false;
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  transferStock(data) {   
    this.service.post('production/manufacturing.php?type=transfer_workorder_pk_dept&id='+this.selectedResult['id'],JSON.stringify(data.value) ).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.isView= false;
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  
  
  calculateFinalYield() {
    let batch_size = Number(this.selectedResult['batch_size']);
    this.total_yeild = (this.actual_yeild - this.total_qty)
    this.yeild_percent = ((this.total_yeild * 100) / batch_size).toFixed(2);
  
  }
  calculateYield() {
    this.gross_total = 0;
    this.tare_total = 0;
    this.net_total = 0;
    let yields = this.selectedStage['yields'];
  
    for (let i = 0; i < yields.length; i++) {
      let temp = yields[i];
      let net = +temp['gross'] - +temp['tare'];
      temp['net'] = net;
  
      if (temp['gross'] !== '' && temp['tare'] !== '') {
        this.gross_total += +temp['gross'];
        this.tare_total += +temp['tare'];
        this.net_total += +temp['net'];
      }
  
      yields[i] = temp;
  
    }
    this.selectedStage['yields'] = yields;
    console.log('nettotaal', this.net_total);
  }
  
  addYieldColumn() {
    let yields = this.selectedStage['yields'];
    let temp = {};
    let count = yields.length + 1;
    temp['yield'] = 'DRUM ' + count;
    temp['gross'] = 0;
    temp['tare'] = 0;
    temp['net'] = 0;
    yields[yields.length] = temp;
  }
  
  completeStage() {
    let temp = this.selectedStage;
    temp['yields'] = this.selectedStage['yields'];
    temp['yield_unit'] = this.yield_unit;
    temp['actual_wt'] = this.net_total;
    temp['yield_qty'] = this.net_total;
  
    this.service.post('production/manufacturing.php?type=completeStage&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'] + '&last_stage=' + this.selectedResult['last_stage'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
      }
    });
  }
  //calculate batch formula//////
  
  calculate() {
  
    let raw_materials = this.selectedResult['raw_materials'];
    for (let i = 0; i < raw_materials.length; i++) {
      let material = raw_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.selectedResult['batch_size'] / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.selectedResult['batch_size']).toFixed(2);
        material['batch_unit'] = material['unit'];
      }
  
      let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];
  
      raw_materials[i] = material;
    }
    this.selectedResult['raw_materials'] = raw_materials;
  
    // let additional_materials = this.selectedResult['additional_materials'];
    // for (let i = 0; i < additional_materials.length; i++) {
    //   let material = additional_materials[i];
    //   if (material['unit'] == "mg") {
    //     material["batch_qty"] = +parseFloat((material["qty"] * (this.selectedResult['batch_size'] / 1000000)) + '').toFixed(2);
    //     material['batch_unit'] = "Kg";
    //   } else {
    //     material["batch_qty"] = (material["qty"] * this.selectedResult['batch_size']).toFixed(2);
    //     material['batch_unit'] = material['unit'];
    //   }
  
    //    let batch_qty = +material["batch_qty"];
    // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
    // material['lot_unit'] = material['batch_unit'];
  
    // additional_materials[i] = material;
    // }
    // this.selectedResult['additional_materials'] = additional_materials;
  
    let packing_materials = this.selectedResult['packing_materials'];
    for (let i = 0; i < packing_materials.length; i++) {
      let material = packing_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.selectedResult['batch_size'] / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.selectedResult['batch_size']).toFixed(2);
        material['batch_unit'] = material['unit'];
      }
  
      let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];
  
      packing_materials[i] = material;
    }
    this.selectedResult['packing_materials'] = packing_materials;
  }
  
  getOperator() {
    this.service.get('common.php?type=getOperators').subscribe(response => {
      this.operators = response;
    })
  }
  setEquipment(data) {
    this.equipment_id = data;
  }
  saveIqpcTestData(data) {
    if (!data.valid) {
      alertify.error("Please Enter Remarks");
      return;
    }
    data.value['bfr_no'] = this.selectedResult['bfr_no'];
    data.value['plan_no'] = this.selectedResult['plan_no'];
    data.value['stage_hdr_id'] = this.selected_ipqc['stage_hdr_id'];
    data.value['stage_dtl_id'] = this.selected_ipqc['id'];
    data.value['remarks'] = data.value['remarks'];
    data.value['lot_no'] = this._lot_no;
    data.value['batch_stage_id'] = this.selected_ipqc['batch_stage_id'];
    this.service.post('production/manufacturing.php?type=save_test_remarks_pk&id=' + this.selected_ipqc['id'], JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getBmrCheckList(this._product_code, this._index);
        this.show_Iqpc_Test = false;
      } else {
        alertify.success(response['status']);
      }
    });
  }
  saveshowProcess(data) {
    if (!data.valid) {
      alertify.error("Please Enter Remarks");
      return;
    }
    if (Number(this.selected_ipqc['batch_stage_id']) <= 0) {
      alertify.error("Please Enter Send TI Sheet information first");
      return;
    }
    data.value['bfr_no'] = this.selectedResult['bfr_no'];
    data.value['plan_no'] = this.selectedResult['plan_no'];
    data.value['stage_hdr_id'] = this.selected_ipqc['stage_hdr_id'];
    data.value['stage_dtl_id'] = this.selected_ipqc['id'];
    data.value['lot_no'] = this._lot_no;
    data.value['batch_stage_id'] = this.selected_ipqc['batch_stage_id'];
    this.service.post('production/manufacturing.php?type=save_process_data_pk&id=' + this.selected_ipqc['batch_stage_id'], JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getBmrCheckList(this._product_code, this._index);
        this.isNewshowProcessForm = false;
      } else {
        alertify.success(response['status']);
      }
    });
  }
  
  saveYeildData(data) {
    if (!data.valid) {
      alertify.error("Please Enter Remarks");
      return;
    }
    if (Number(this.selected_ipqc['batch_stage_id']) <= 0) {
      alertify.error("Please Enter Send TI Sheet information first");
      return;
    }
    data.value['bfr_no'] = this.selectedResult['bfr_no'];
    data.value['plan_no'] = this.selectedResult['plan_no'];
    data.value['stage_hdr_id'] = this.selected_ipqc['stage_hdr_id'];
    data.value['stage_dtl_id'] = this.selected_ipqc['id'];
    data.value['lot_no'] = this._lot_no;
    data.value['batch_stage_id'] = this.selected_ipqc['batch_stage_id'];
    this.service.post('production/manufacturing.php?type=save_yeild_data&id=' + this.selected_ipqc['batch_stage_id'], JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.getBmrCheckList(this._product_code, this._index);
        this.isShowYeild = false;
      } else {
        alertify.success(response['status']);
      }
    });
  }
  
  getCurrentTime(action, value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    /* let time = new Date().toLocaleTimeString(); */
  if (value =="area_clean") {
      if (action == 'from_time') {
        this.start_time = h + ':' + m;
        this.isTime=true;
      } else {
        this.end_time = h + ':' + m;
      }
    } 
  }
  
  }
  
