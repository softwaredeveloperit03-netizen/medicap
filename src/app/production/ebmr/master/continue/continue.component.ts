import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-continue',
  templateUrl: './continue.component.html',
  styleUrls: ['./continue.component.css']
})
export class ContinueComponent implements OnInit {
  lots = 0;
  isView = false;
  results;
  batchList=[];
  selectedIndex = -1;
  selectedResult = [];
  addtionBatchList=[];
  materials;
  amaterials;
  units;

  materialList = [];
  additionalList = [];

  packings;
  packingList = [];
  selectedMaterial = [];
  selectedMaterial1 = [];

  isLimit = false;
  isLessthan = false;
  isMorethan = false;

  composite = 0;
  micro_qty = 0;
  tests;
  subtests;
  testsList = [];
  revisionList = [];
  batchraw=[];
  composite1 = 0;
  micro_qty1 = 0;
  testsList1 = [];
  revisionList1 = [];

  rawMaterials = [];

  equipments;
  stages;
  batch_size = 0;
  equipmentList = [];
  selectedBatch= [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessMFRs();
  }

  getInprocessMFRs() {
    this.service.get('bmr/mfr.php?type=getInprocessMFRs').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.view(this.selectedIndex);
      }
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.selectedBatch=this.selectedResult['batch_materials'];
    this.selectedIndex = index;

    if (this.selectedResult['status'] == 'pending') {
      if (this.selectedResult['israw'] == 'pending' || this.selectedResult['ispacking'] == 'pending') {
        this.getUnits();
      }
      if (this.selectedResult['isspecification'] == 'pending') {
        this.getTests();
      }
      if (this.selectedResult['isequipment'] == 'pending') {
        this.getEquipments();
      }
  
      let flag = 0;
      this.stages = [];
      let stages = this.selectedResult['stages'];
      
      stages.forEach(element => {
        if (element['process_stage'] != 'DISPENSING') {
          this.stages[this.stages.length] = element;
        }
        if (element['status'] == 'pending') {
          flag = 1;
        }
      });
      
      if (flag == 1) {
        this.isView = true;
      } else {
        alert('MFR already completed');
      }
    }
  }

  getApprovedRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_type='+ value).subscribe(response => {
      this.materials = response;
    });
  }

  getAdditionalRawMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_type='+ value).subscribe(response => {
      this.amaterials = response;
    });
  }

  getUnits() {
    this.service.get('production/unitformula.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  selectMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
    }
  }

  selectMaterial1(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial1 = this.amaterials[index];
    }
  }

  addRaw(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['grade'] = this.selectedMaterial['grade'];
    this.rawMaterials[this.rawMaterials.length] = temp;
    data.resetForm();
  }

  dele(index) {
    this.rawMaterials.splice(index, 1);
  }

  addAdditional(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['material_name'] = this.selectedMaterial1['material_name'];
    temp['grade'] = this.selectedMaterial1['grade'];
    this.additionalList[this.additionalList.length] = temp;
    data.resetForm();
  }

  delAdditional(index) {
    this.additionalList.splice(index, 1);
  }

  getMaterial(value){
    this.service.get('common.php?type=getMaterialsByType&material_type=' +value).subscribe(response => {
      this.packings = response;
    });
  }

  addPage(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    this.packingList[this.packingList.length] = temp;
    data.resetForm();
  }


  del(index) {
    this.packingList.splice(index, 1);
  }

  addbatch(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'Primary Packing';
    this.batchList[this.batchList.length] = temp;
    data.resetForm();
  }

  delbatch(index){
    this.batchList.splice(index, 1);
  }

  addbatchraw(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['role'] = 'API';
    this.batchraw[this.batchraw.length] = temp;
    data.resetForm();
  }
  addbatchAdditional(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.addtionBatchList[this.addtionBatchList.length] = temp;
    data.resetForm();
  }
  delbatchAdd(index){
    this.additionalList.splice(index,1);
  }

  saveStandardBatchFormula(){
    if(this.selectedResult['israw'] == 'done' && this.selectedResult['ispacking']=='done'){
      let temp={};
      // temp['raw_materials']=this.batchraw;
      // temp['packing_materials']=this.batchList;
      // temp['additional_materials']=this.addtionBatchList;
      temp['raw_materials']=this.selectedResult['raw_materials'];
      temp['packing_materials']=this.selectedResult['packing_materials'];
      temp['additional_materials']=this.selectedResult['additional_materials'];
      this.service.post('bmr/mfr.php?type=saveStandardBatchSize&id=' + this.selectedResult['id']+'&batch_size='+this.batch_size +'&lots='+this.lots, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] !== 'success') {
          alert('Failed: An error occured, please try again!');
        } else {
          this.getInprocessMFRs();
          this.selectedResult = this.results[this.selectedIndex];
          this.selectedResult['isbatch'] = 'done';
        }
      });
    }else{
      alert('Please Fill unit Formula and Packing Formula');
    }
  }

  save() {
    /* this.service.post('bmr/mfr.php?type=saveMaster', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      }
    }); */
  }

  // Specification
  getLimitcheck(getval) {
    if (getval === 'Limits') {
      this.isLessthan = false;
      this.isMorethan = false;
      this.isLimit = true;
    } else if (getval === 'LessThan') {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = true;
    } else if (getval === 'MoreThan') {
      this.isLimit = false;
      this.isMorethan = true;
      this.isLessthan = false;
    } else {
      this.isLimit = false;
      this.isMorethan = false;
      this.isLessthan = false;
    }
  }

  addTest(data) {
    if (!data.valid) {
      alert('Please complete all required fields');
      return;
    }
    let temp = data.value;
    this.testsList[this.testsList.length] = data.value;
    if (temp['for_microbiology'] == 'Yes') {
      this.micro_qty += +temp['sample_qty'];
    } else {
      this.composite += +temp['sample_qty'];
    }
    data.resetForm();
  }

  deleteTest(index) {
    let temp = this.testsList[index];
    if (temp['for_microbiology'] == 'Yes') {
      this.micro_qty -= +temp['sample_qty'];
    } else {
      this.composite -= +temp['sample_qty'];
    }
    this.testsList.splice(index, 1);
  }

  addRevision(data) {
    if (!data.valid) {
      alert('Please complete all required fields');
      return;
    }
    this.revisionList[this.revisionList.length] = data.value;
  }

  saveFinishSpec(data) {
    if (!data.valid) {
      alert('Please complete all required fields');
      return;
    }
    let temp = data.value;
    temp['tests'] = this.testsList;
    temp['revisions'] = this.revisionList;
    temp['product_code'] = this.selectedResult['product_code'];
    temp['unit'] = this.selectedResult['unit'];
    temp['next_review_date'] = '';
    temp['retest_period'] = '';
    this.service.post('bmr/mfr.php?type=saveFinishSpec', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getInprocessMFRs();
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['isspecification'] = 'done';
      }
    });
  }

  saveRawUnitFormula() {
    let temp = {};
    temp["raw_materials"] = this.rawMaterials;
    temp['additional_materials'] = this.additionalList;
    this.service.post('bmr/mfr.php?type=saveRawUnitFormula&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getInprocessMFRs();
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['israw'] = 'done';
      }
    });
  }

  savePackingUnitFormula() {
    this.service.post('bmr/mfr.php?type=savePackingUnitFormula&id=' + this.selectedResult['id'], JSON.stringify(this.packingList)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getInprocessMFRs();
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['ispacking'] = 'done';
      }
    });
  }

  // saveStandardBatchFormula() {
  //   let temp = [];
  //   this.service.post('', JSON.stringify(temp)).subscribe(response => {
      
  //   });
  // }

  getTests() {
    this.service.get('qc/specification/finish.php?type=getTests').subscribe(response => {
      this.tests = response;
    });
  }

  getSubtest(index) {
    index = index - 1;
    if (index !== -1) {
      this.subtests = this.tests[index].subtests;
    }
  }

  saveInprocessSpec(data) {
    if (!data.valid) {
      alert('Please complete all required fields');
      return;
    }
    let temp = data.value;
    temp['tests'] = this.testsList;
    temp['revisions'] = this.revisionList;
    temp['product_code'] = this.selectedResult['product_code'];
    temp['unit'] = this.selectedResult['unit'];
    temp['next_review_date'] = '';
    temp['retest_period'] = '';
    this.service.post('bmr/mfr.php?type=saveInprocessSpec', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getInprocessMFRs();
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['isinprocess'] = 'done';
      }
    });
  }

  addTest1(data) {
    if (!data.valid) {
      alert('Please complete all required fields');
      return;
    }
    let temp = data.value;
    this.testsList1[this.testsList1.length] = data.value;
    if (temp['for_microbiology'] == 'Yes') {
      this.micro_qty1 += +temp['sample_qty'];
    } else {
      this.composite1 += +temp['sample_qty'];
    }
    data.resetForm();
  }

  deleteTest1(index) {
    let temp = this.testsList1[index];
    if (temp['for_microbiology'] == 'Yes') {
      this.micro_qty1 -= +temp['sample_qty'];
    } else {
      this.composite1 -= +temp['sample_qty'];
    }
    this.testsList1.splice(index, 1);
  }

  addRevision1(data) {
    if (!data.valid) {
      alert('Please complete all required fields');
      return;
    }
    this.revisionList1[this.revisionList1.length] = data.value;
  }

  getEquipments() {
    this.service.get('bmr/mfr.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  selectedEquipment = [];
  selectEquipment(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEquipment = this.equipments[index];
    } else {
      this.selectedEquipment = [];
    }
  }

  selectedStage = [];
  selectStage(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedStage = this.stages[index];
    } else {
      this.selectedStage = [];
    }
  }

  addEquipment(data) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['min_capacity'] = this.selectedEquipment['min_capacity'];
    temp['max_capacity'] = this.selectedEquipment['max_capacity'];
    temp['make'] = this.selectedEquipment['make'];
    temp['stage'] = this.selectedStage['stage'];
    temp['step'] = this.selectedStage['step'];
    this.equipmentList[this.equipmentList.length] = temp;
    data.resetForm();
    this.selectedStage = [];
    this.selectedEquipment = [];
  }

  delEquipment(index) {
    this.equipmentList.splice(index, 1);
  }

  saveEquipment() {
    this.service.post('bmr/mfr.php?type=saveEquipment&id=' + this.selectedResult['id'], JSON.stringify(this.equipmentList)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getInprocessMFRs();
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['isequipment'] = 'done';
      }
    });
  }

  saveStage() {
    this.service.post('bmr/mfr.php?type=saveStage&id=' + this.selectedResult['id'], JSON.stringify(this.stages)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        alert('save stage successfully');
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['isstages'] = 'done';
        this.getInprocessMFRs();
      }
    });
  }

  saveStep() {
    this.service.post('bmr/mfr.php?type=saveStep&id=' + this.selectedResult['id'], JSON.stringify(this.stages)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        this.getInprocessMFRs();
        this.selectedResult = this.results[this.selectedIndex];
        this.selectedResult['steps'] = this.stages;
        this.selectedResult['isstep'] = 'done';
      }
    });
  }

  close() {
    this.selectedResult = [];
    this.isView = false;
  }

  add(index, data, step) {
    let stages = this.selectedResult['steps'];
    let stage = stages[index];

    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    if (step == 'instruction') {
      let instructions = [];
      if (stage['instructions'] == undefined || stage['instructions'] == null) {
        instructions[instructions.length] = data.value;
      } else {
        instructions = stage["instructions"];
        instructions[instructions.length] = data.value;
      }
      stage['instructions'] = instructions;
    } else if (step == 'clearance') {
      let clearances = [];
      if (stage['clearances'] == undefined || stage['clearances'] == null) {
        clearances[clearances.length] = data.value;
      } else {
        clearances = stage["clearances"];
        clearances[clearances.length] = data.value;
      }
      stage['clearances'] = clearances;
    } else if (step == 'procedure') {
      let procedures = [];
      if (stage['procedures'] == undefined || stage['procedures'] == null) {
        procedures[procedures.length] = data.value;
      } else {
        procedures = stage["procedures"];
        procedures[procedures.length] = data.value;
      }
      stage['procedures'] = procedures;
    } else if (step == 'checks') {
      let checks = [];
      if (stage['checks'] == undefined || stage['checks'] == null) {
        checks[checks.length] = data.value;
      } else {
        checks = stage["checks"];
        checks[checks.length] = data.value;
      }
      stage['checks'] = checks;
    }
    stages[index] = stage;
    this.selectedResult['steps'] = stages;
    data.resetForm();
  }

  delData(index, step, index1) {
    let stages = this.selectedResult['steps'];
    let stage = stages[index];

    if (step == 'instruction') {
      let instructions = stage["instructions"];
      instructions.splice(index1, 1);
      stage['instructions'] = instructions;
    } else if (step == 'clearance') {
      let clearances = stage["clearances"];
      clearances.splice(index1, 1);
      stage['clearances'] = clearances;
    } else if (step == 'procedure') {
      let procedures = stage["procedures"];
      procedures.splice(index1, 1);
      stage['procedures'] = procedures;
    } else if (step == 'checks') {
      let checks = stage["checks"];
      checks.splice(index1, 1);
      stage['checks'] = checks;
    }
    stages[index] = stage;
    this.selectedResult['steps'] = stages;
  }

  saveStageDetails(index) {
    let stages = this.selectedResult['steps'];
    let stage = stages[index];

    if (stage['isinstruction'] == 'yes' && stage['instructions'].length == 0) {
      alert('General Instructions List is Empty!');
      return;
    }
    if (stage['isclearance'] == 'yes' && stage['clearances'].length == 0) {
      alert('Line Clearance Checklist is Empty!');
      return;
    }
    if (stage['isprocedure'] == 'yes' && stage['procedures'].length == 0) {
      alert('Procedures List is Empty!');
      return;
    }
    let checks = stage['checks'];
    if (stage['ischecks'] == 'yes' && checks.length == 0) {
      alert('Inprocess Checkpoints List is Empty!');
      return;
    }
    this.service.post('bmr/mfr.php?type=saveStageDetails&id=' + this.selectedResult['id'], JSON.stringify(stage)).subscribe(response => {
      if (response['status'] !== 'success') {
        alert('Failed: An error occured, please try again!');
      } else {
        stages[index].status = 'done';
        this.getInprocessMFRs();
      }
    });
  }

  calculate() {
    let raw_materials = this.selectedResult['raw_materials'];
    for (let i = 0; i < raw_materials.length; i++) {
      let material = raw_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      raw_materials[i] = material;
    }
    this.selectedResult['raw_materials'] = raw_materials;

    let additional_materials = this.selectedResult['additional_materials'];
    for (let i = 0; i < additional_materials.length; i++) {
      let material = additional_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      additional_materials[i] = material;
    }
    this.selectedResult['additional_materials'] = additional_materials;

    let packing_materials = this.selectedResult['packing_materials'];
    for (let i = 0; i < packing_materials.length; i++) {
      let material = packing_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = (material["qty"] * (this.batch_size / 1000000)).toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

      let batch_qty = +material["batch_qty"];
      material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      material['lot_unit'] = material['batch_unit'];

      packing_materials[i] = material;
    }
    this.selectedResult['packing_materials'] = packing_materials;
  }


}
