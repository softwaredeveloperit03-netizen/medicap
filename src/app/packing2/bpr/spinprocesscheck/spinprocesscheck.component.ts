import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-spinprocesscheck',
  templateUrl: './spinprocesscheck.component.html',
  styleUrls: ['./spinprocesscheck.component.css']
})
export class SpinprocesscheckComponent implements OnInit {

  
  
  
  
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
    // this.getOperator();
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  
    // this.getEquipments();
  }
  check_bmr(){

    let temp= {};
    // temp['pk_equip_list'] = this.pk_equip_list;
    this.service.post('packing/process.php?type=bmr_approve&work_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
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
    this.service.post('packing/process.php?type=SAVE_bmr_Equipment_data&work_id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
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

  Filling_qty2;
packing_type2;


  deviation;
defect_observed;
units_checked;

  deleteEquipments(index){
    this.pk_equip_list.splice(index, 1);
  }
  
  getInprocessBatches() {
    //this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Production_Activity_Formulation&material_type=Raw Material').subscribe(response => {
    this.service.get('production/product.php?type=getReadyBatchPlans_pk_sp_check&material_type=Packing Material').subscribe(response => {
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
    this.service.post('packing/sampling.php?type=sendIntimation_saipro',JSON.stringify(this.selectedResult)).subscribe(response=>{
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
    this.get_int_sift();
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
        
        }
      }
  
      this.isShow = true;
    }); 
    
  }

  
int_sifters;
isShow = false;  
get_int_sift() {
  this.service.get('production/product.php?type=get_savebmr_sift_pk_inprocess_check_pk&work_order_no='+this.selectedResult['work_order_no']+'&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_id='+this.selectedResult['a_id']).subscribe(response => {
    this.int_sifters = response;
  });
}

selected_sifter=[];
add(index){
  this.selected_sifter=this.int_sifters[index]  
  this.isView = true;
  this.isShow = false;  
}
  view(index) {
  
     this.selectedStage = this.selectedResult['stage'];
  
      this.isView = true;
  }

  
  
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
  }
  disp_materials;
  get_disp_material() {
    this.service.get('packing/process.php?type=getDisp_material&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_order_id='+this.selectedResult['a_id']+'&sift_id='+this.selected_sifter['id']).subscribe(response => {
      this.disp_materials = response;
    });
  }
  
 
  pkequipments
  getequipments(){
    this.service.get('equipments.php?type=getEquipmentNames&department1=Packing').subscribe(response =>{
      this.pkequipments = response;
    });
  }
  pk_equip_list=[];
  selectedEquipment_data=[]
 
  
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
  
