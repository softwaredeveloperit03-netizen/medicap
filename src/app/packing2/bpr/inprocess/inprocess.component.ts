import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {

//   isView = false;
//   results;

//   operators;
//   primary=[];
//   secondary=[];
//   tertiary=[];
//   materials=[];

//   selectedResult = [];
//   constructor(private service: DataAccessService) { }

//   ngOnInit() {
//     this.getStartedBatches();
//   }

//   getStartedBatches() {
//     this.service.get('packing/process.php?type=getStartedBatches').subscribe(response => {
//       this.results = response;
//     });
//   }

//   view(index) {
//     this.selectedResult = this.results[index];
//     this.isView = true;
//   }

//   complete(data) {
//     if (!data.valid) {
//       alertify.error('All fields are required!');
//       return;
//     }
//     let temp = data.value;
//     temp['id'] = this.selectedResult['id'];
//     this.service.post('packing/process.php?type=completePacking', JSON.stringify(temp)).subscribe(response => {
//       if (response['status'] == 'success') {
//         alertify.success(response['msg']);
//         this.isView = false;
//         this.getStartedBatches();
//       } else {
//         alertify.error(response['msg']);
//       }
//     });
//   }

// }




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
  this.getEquipments();
  this.getOperator();
  this.service.observableUnit.subscribe(response => {
    this.units = response;
  });

  this.getEquipments();
}

getInprocessBatches() {
  //this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Production_Activity_Formulation&material_type=Raw Material').subscribe(response => {
  this.service.get('production/product.php?type=getReadyBatchPlans_pk&material_type=Packing Material').subscribe(response => {
    this.results = response;
    this.results = response;
    if (this.selectedIndex !== -1 && this.results?.length > 0) {
      this.view(this.selectedIndex);
    } else {
      this.isView = false;
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
  //  this.selectedSpecification = this.selectedResult['ti_sheet'];
  this.batch_yeild_max = this.selectedResult['max_yeild'];
  this.service.get('store/dispensing.php?type=get_bmr_checklist_by_product_Code_pk&product_code=' + product_code).subscribe(response => {
    this.bmr_info = response;
    no_of_lots = this.selectedResult['no_of_lots'];
    // this.bmr_checllist = JSON.parse(this.bmr_info['bmr_checklist']);
    // stage_wise_lot_data = this.bmr_info['stage_process_details'];
    //  this.ti_sheet = this.bmr_info['ti_sheet'];
    //  this.eq_usage = this.bmr_info['eq_usage'];
    // for (var x = 0; this.bmr_checllist.length; x++) {
    //   if (this.bmr_checllist[x] == undefined || this.bmr_checllist[x] == null) {
    //     break;
    //   }
    //   this.bmr_checllist[x]['batch_stage_id'] = 0;
    //   this.bmr_checllist[x]['remarks_entry_by'] = '';
    //   this.bmr_checllist[x]['process_entry_by'] = '';
    //   this.bmr_checllist[x]['yeild_entry_by'] = '';
    //   this.bmr_checllist[x]['ipqc_status'] = '';
    // }
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

    this.isView = true;
  });
}
view(index) {

   this.selectedStage = this.selectedResult['stage'];

    /* let yields = [{"yield": "DRUM 1", "gross": 0, "tare": 0, "net": 0}];
    this.selectedStage['yields'] = yields;
    this.calculate();

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
    this.selectedDispensing =this.selectedResult['dispensing'];*/
    // this.selectedSpecification = this.selected_ipqc['ti_sheet'];
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
}

tisheet() {
  this.isNewTI = true;
}

getEquipments() {
  this.service.get('common.php?type=getEquipments').subscribe(response => {
    this.equip = response;
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

