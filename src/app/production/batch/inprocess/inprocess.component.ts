import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {

  isView = false;
  results;
  selectedIndex = -1;
  selectedResult = [];
  selectedStage = [];
  units;
  selectedPage = 0;
  isNewIncident = false;
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
  selectedContainer =[];
  selectedMaterial = [];
  isNewTI= false;
  equip;
  selectedEquip =[];
  isTechnicalInfo = false;
  selectedSpecification = [];
  specificationTest= [];
  specTest =[];
  constructor(public service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getInprocessBatches();  
    this.service.observableUnit.subscribe(response=>{
      this.units = response;
    });

    this.getEquipments();
  }

  getInprocessBatches() {
    this.service.get('production/inprocess.php?type=getInprocessBatches').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1 && this.results?.length > 0) {
        this.view(this.selectedIndex);
      } else {
        this.isView = false;
      }
    });
  }

  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.selectedStage = this.selectedResult['stage'];

    // let yields = [{"yield": "DRUM 1", "gross": 0, "tare": 0, "net": 0}];
    // this.selectedStage['yields'] = yields;
    // this.calculate();

    let page_no = 10;
    let completedStages = this.selectedResult['complete_stages'];
    for (let i = 0; i < completedStages.length; i++) {
      let stage = completedStages[i];
      stage['page_no'] = page_no;
      page_no++;
    }
    this.selectedResult['complete_stages'] = completedStages;

    this.selectedResult['page_no'] = page_no;
    page_no++;

    let pendingStages = this.selectedResult['pending_stages'];
    for (let i = 0; i < pendingStages.length; i++) {
      let stage = pendingStages[i];
      stage['page_no'] = page_no;
      page_no++;
    }
    this.selectedResult['pending_stages'] = pendingStages;
    this.selectedDispensing =this.selectedResult['dispensing'];
    this.selectedSpecification = this.selectedResult['inprocessSpecification'];
    this.isView = true;
  }

  viewshow(index){
    let material = this.selectedDispensing['materials'];
    this.selectedMaterial = material[index];
    this.selectedContainer=this.selectedMaterial['containers'];
    console.log(this.selectedContainer);
    this.isViewshow=true;
  }

  selectPage(index) {
    this.selectedPage = index;
  }

  tisheet(){
    this.isNewTI = true;
  }

  getEquipments(){
    this.service.get('common.php?type=getEquipments').subscribe(response=>{
      this.equip = response;
    });
  }

  getEquipmentsbycode(index){
    this.selectedEquip = this.equip[index];
     console.log('ff',this.selectedEquip );
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

  send(data){
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

  saveInprocessCheck() {
    this.service.post('production/manufacturing.php?type=saveInprocessCheck&id=' + this.selectedStage['id'] + '&bmr_no=' + this.selectedResult['bmr_no'] + '&batch_no=' + this.selectedResult['batch_no'] + '&stage=' + this.selectedResult['current_stage'], JSON.stringify(this.selectedStage['checks'])).subscribe(response => {
      if (response['status'] == 'success') {
        this.getInprocessBatches();
        alertify.success(response['msg']);
      } else {
        alertify.success(response['msg']);
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

  gross_total = 0;
  tare_total = 0;
  net_total = 0;
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
    console.log('nettotaal' , this.net_total);
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
    temp['yield_unit'] =this.yield_unit;
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


}
