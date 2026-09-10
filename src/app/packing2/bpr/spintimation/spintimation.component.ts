import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-spintimation',
  templateUrl: './spintimation.component.html',
  styleUrls: ['./spintimation.component.css']
})
export class SpintimationComponent implements OnInit {

  
  
  
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
  constructor(public service: DataAccessService, private router: Router) {
    // this.bath_commencent_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    // this.bath_complete_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
  
  ngOnInit() {
    this.getInprocessBatches();
    // this.getEquipments();

    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  
    // this.getEquipments();
  }
  getInprocessBatches() {
    //this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Production_Activity_Formulation&material_type=Raw Material').subscribe(response => {
    this.service.get('production/product.php?type=getReadyBatchPlans_pk_sp_intimation&material_type=Packing Material').subscribe(response => {
      this.results = response;
      
    });
  }
  getBmrCheckList(product_code, index) {
    this.isShow = true;
   
    this.selectedResult = this.results[index];
    
    this.get_int_sift()
  }


  int_sifters;
  isShow = false;  
  get_int_sift() {
    this.service.get('production/product.php?type=get_savebmr_sift_pk_intimation_pk&work_id='+this.selectedResult['id']).subscribe(response => {
      this.int_sifters = response;
    });
  }
  
  selected_sifter=[];
  add(index){
    this.selected_sifter=this.int_sifters[index]  
    this.isYieldStatement = true;
    this.isShow = false;  
    this.get_disp_material();
  }
  



 




  disp_materials;
  get_disp_material() {
    this.service.get('packing/process.php?type=getDisp_material&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_order_id='+this.selectedResult['id']+'&sift_id='+this.selected_sifter['id']).subscribe(response => {
      this.disp_materials = response;
    });
  }
  save_qc_qty(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
   
    this.service.post('production/product.php?type=save_qc_sample_qty&sift_id='+this.selected_sifter['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        data.reset();
        this.isYieldStatement= false;
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  
  }
  
