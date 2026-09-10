import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-standard',
  templateUrl: './standard.component.html',
  styleUrls: ['./standard.component.css']
})
export class StandardComponent implements OnInit {
  selectedEquipments = [];
  results: any = [];
  isView = false;
  isStart = false;
  id;
  dat;
  labors:any=[];

  isrange =false;
  water;
  envfrequency ='';
  islessthan =false;
  ismorethan = false;
  // isStart = false;
  isWahRinseWater =false;
  equipments;
  iscleranceAdd = false;
  isViewCleaaranceChecklist = false;
  isViewEquipments = false;
  isEquipmentAdd=false;
  isInstructionView = false;
  isInstructionAdd = false;
  isInprocessAdd = false;
  isInprocessView = false;
  isEnvironmentAdd=false;
  isEnvironmentView=false;
  isWeighingsAdd= false;
  isWeighingsView= false;
  isProcedureView=false;
  isProcedureAdd=false;
  instructionList=[];
  inprocessList=[];
  environmentList=[];
  checkList = [];
  selectedResult = [];
  weighingsList=[];
  procedureList=[];
  initialCheckList=[];
  integrityCheckList=[];
  generalIntegrity=[];
  selectStage = [];
  ipqc;
  selectedIndex = -1;
  frequency;
  isclerance='YES';
  isinstruction='YES';
  ischeck='YES';
  isProduction='YES';
  isQa='YES';
  isQc='YES';
  env_production='YES';
  env_qa='YES';
  isenvironment = 'YES';
  isweighing= 'YES';
  weighing_production='YES';
  weighing_qa='YES';
  isprocedure='YES';
  isNewStage=false;
  selectedPage = 0;
  selectedwater=[];
  selewater = false;
  equipmentsType;
  batch_size = 100000;
  isTISheet = false;
  newStageMaster = false;
  fre_unit = '';
  options = [
    { "name": "QA","option": "qa", "status": false, "list": []},
    { "name": "Production","option": "production", "status": false, "list": []}
  ];
  options1 = [
    { "name": "Equipments / Instruments","option": "equipment", "status": true, "list": []}
   ];
  units;
  isnewIpqcForm = false;
  lineClearance =[
    { "Description":"Name of the previous product" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Medicap Lot No" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Temperature of the room" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Relative humidity" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Pressure differential of the area" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Cleanliness of area" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Removal of previous products" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Verification of balance" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Cleanliness of dispensing booth& dispensing tools" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Is there are approved label affix on a drum of raw material" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Check all the respective" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Relevant status labels affixed for the proceeding batch" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Cleaning Of UV lamp" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
    {"Description":"Exterior Of lamp" ,"Observation": "" ,"check_by" :"" ,"verify_date":""},
  ];

  stagemaster;
  processes = [];
  dosage_form = '';
  process_type = '';
  dosages;
  bmrList = [];
  spec;
  selectSpec;
  ipqccheck;
  plant_type;
  constructor(private service: DataAccessService, public route: ActivatedRoute) { 
    this.plant_type = this.service.getPlantConfigFields('plant_type');

  }

  ngOnInit() {
    this.getManufacturingStages();
  this.getsampleWashWater();
    this.getEquipmentDetails();
   this.getProcesses();
   this.getequipments();
    this.getDosages();
    this.getSpecification();
    this.getSection();
    this.getUnits();
    this.getApprovedLabors();
    
    this.getIPQC();
    console.log(this.labors);
  }
  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response=>{
      this.units = response;
    });
  }
  selectedMfr=[];
  selectMfr(index){
    this.selectedMfr=this.selectedResult['unitformula'][index];
    console.log(this.selectedMfr);
  }

  sheet(){
    this.isTISheet = true;
  }
  ch_list = '';
  checklistList=[];
  addData(data) {
    // let list = this.options[index].list;
    // list[list.length] = this.ch_list;
    // this.ch_list = '';
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.checklistList[this.checklistList.length] = temp;
    console.log(this.checklistList);
    data.resetForm();
  }
  delData(index) {
    this.checklistList.splice(index, 1);
  }
  prod_checklistList=[];
  addDataProd(data) {
    // let list = this.options[index].list;
    // list[list.length] = this.ch_list;
    // this.ch_list = '';
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.prod_checklistList[this.prod_checklistList.length] = temp;
    console.log(this.prod_checklistList);
    data.resetForm();
  }
  delDataProd(index) {
    this.prod_checklistList.splice(index, 1);
  }
  getManufacturingStages() {
    this.service.get('production/stages.php?type=getManufacturingStages').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.view(this.selectedIndex);
      }
     
    });
  }

 


  getequipments(){
    this.service.get('equipments.php?type=getEquipmentNames').subscribe(response =>{
      this.equipments = response;
    });
  }

  getsampleWashWater(){
    this.service.get('qa/washwater.php?type=getsampleWashWater').subscribe(response=>{
      this.water = response;
    });
  }

  getSpecification(){
    this.service.get('production/stages.php?type=getSpecificationsLog&dosage_form='+'' +'&grade=' + '' + '&status=' + '').subscribe(response=>{
      this.spec = response;
    });
  }
  processes1= [];
  addnewProcess1(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.processes1[this.processes1.length] = temp;
    data.resetForm();
  }
  viewwater(index){
    this.selectedwater =this.water[index];
    this.selewater = true;
  }
  start(data){
    // this.selectedwater =this.water[index];
    if(this.processes1.length==0){
      this.isStart = true;
      this.isView = false;
    }
    else{
     
      console.log(data.value);
      if (!data.valid) {
        alert('All fields are required');
        return;
      }
      let temp = data.value;
      temp['processes1']=this.processes1;
  
      this.service.post('bmr/process.php?type=saveEbmrProcess&dosage_form='+this.selectedResult['dosage_form'], JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('checklist Saved Successfully');
          this.isStart = true;
          this.isView = false;
          this.getProcesses();
        } else {
          console.log(response);
          alert('Failed: An error occured, please try again!');
        }
      });
    }
   
  }

  getIPQC(){
    this.service.get('production/ipqc.php?type=getIPQC').subscribe(response=>{
      this.ipqccheck = response;
    });
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getEquipmentDetails(){
    this.service.get('common.php?type=getEquipments').subscribe(response =>{
      this.equipmentsType = response;
    });
  }

  getEquipmentCode(index){
    index = index-1;
    if(index !== -1){
      this.equipmentsType = this.equipments[index];
      console.log('equipments', this.equipmentsType);
    }
  }

  selectEquipments =[];
  stage_selected;
  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.isView = true;
    this.calculate();
    let page_no = 10;
    let stages = this.selectedResult['stages'];
    for (let i = 0; i < stages.length; i++) {
      let stage = stages[i];
      stage['page_no'] = page_no;
      page_no++;
    }
    this.selectedResult['stages'] = stages;
    this.getequipments();
    
    // this.selectSpec = this.spec[index]; 
   
    this.isView = true;
  }

  stageMaster(){
    this.newStageMaster = true;
  }

  addnewProcess(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.processes[this.processes.length] = temp;
    data.resetForm();
  }

  saveProcess() {
    for (let i = 0; i < this.processes.length; i++) {
      this.processes[i].dosage_form = this.dosage_form;
      this.processes[i].process_type = this.process_type;
    }
    this.service.post('bmr/process.php?type=saveProcess', JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Processes Saved Successfully');
        this.dosage_form = '';
        this.processes = [];
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  getProcesses(){
    this.service.get('bmr/process.php?type=getProcesses').subscribe(response =>{
      this.stagemaster =response;
    });
  }
  // getProcesses(){
  //   this.service.get('bmr/process.php?type=getProcesses').subscribe(response =>{
  //     this.stagemaster =response;
  //   });
  // }
  sections;
  getSection(){
    this.service.get('common.php?type=getDepartmentSections&department=Production').subscribe(response =>{
      this.sections =response;
    });
  }
  selectedstep=[];
  selectedsteps=[];
  stepssss(index){
    // index = index +1
   this.selectedstep=this.selected_stages[index];
   this.selectedsteps=this.selectedstep['steps'];
   console.log(this.selectedstep);
   console.log(this.selectedsteps);

//     this.selectedstep.length=0;
// for(let i=0;i<this.selectStage1.length;i++){
//   if(this.selectStage1[i]['stage']==value){
//     this.selectedstep[this.selectedstep.length]=this.selectStage1[i];
   
//   }
// }
// console.log(this.selectedstep);
// console.log('hi')
  }

  selectStage1=[];
  selected_stage=[];
  selected_stages=[];
  stagess(index){
   
    this.selected_stage=this.select_Stage[index];
    this.selected_stages=this.selected_stage['stages'];
    // this.selectStage1 = this.processes1;
    // console.log(this.selectStage1)
    // console.log(this.processes1)
    console.log(this.selected_stage);
    console.log(this.selected_stages);

  }
  // getStageMasterDetails(index){
  //   index = index -1;
  //   if (index !== -1) {
  //     this.selectStage = this.stagemaster[index];
      
  //   } else {
  //     this.selectStage = this.processes1;
  //   }
  //   console.log(this.processes1);
  // }
  select_Stage=[];
  getStageMasterDetails(index){
   
      this.selectStage = this.stagemaster[index];
      this.select_Stage = this.selectStage['process_types'];
  
  }
  S_stage;
  selectedStageIndex = -1;


  selectPage(index, index1) {
    this.selectedPage = index;
  
    this.S_stage=this.selectedResult['stages'][index1];
    if (index > 9 && index1 !== -1) {
      let stages = this.selectedResult['stages'];
      this.selectedStage = stages[index1];
      this.selectedStageIndex = index;
    } else {
      this.selectedStage = [];
    }
    // console.log(this.selectedPage['id'])
    //  console.log(this.selectedStage['stages'].id);
     console.log(this.selectedPage);
     console.log(this.selectedResult['stages'][index]);
     console.log(this.S_stage['id']);
     this.getApprovedLabors();
     
  }

  selectedStage = [];
  iscleranceView(id) {
    this.id = id;
    this.iscleranceAdd = true;
  }

  
 
  getLimits(value){
    if(value == 'LessThan'){
      this.islessthan =true;
      this.ismorethan = false;
      this.isrange == false;
    }else if(value == 'MoreThan'){
      this.islessthan =false;
      this.ismorethan = true;
      this.isrange == false;
    }else if(value == 'Range'){
      this.isrange == true;
      this.islessthan =true;
      this.ismorethan = true;
    }else if(value == 'Compliences'){
      this.isrange == false; 
      this.islessthan =false;
      this.ismorethan = false;
    }
  }

  saveIPQCChecks(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp = data.value;
    this.service.post('production/ipqc.php?type=saveIPQC', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('IPQC Saved Successfully');
        this.isnewIpqcForm = false;
        this.getManufacturingStages();
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }


  calculate() {
    console.log('batxh',this.batch_size);
    let raw_materials = this.selectedResult['raw_materials'];
    for (let i = 0; i < raw_materials.length; i++) {
      let material = raw_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.batch_size / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

       let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];

      raw_materials[i] = material;
    }
    this.selectedResult['raw_materials'] = raw_materials;

    let additional_materials = this.selectedResult['additional_materials'];
    for (let i = 0; i < additional_materials.length; i++) {
      let material = additional_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.batch_size / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

       let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];

      additional_materials[i] = material;
    }
    this.selectedResult['additional_materials'] = additional_materials;

    let packing_materials = this.selectedResult['packing_materials'];
    for (let i = 0; i < packing_materials.length; i++) {
      let material = packing_materials[i];
      if (material['unit'] == "mg") {
        material["batch_qty"] = +parseFloat((material["qty"] * (this.batch_size / 1000000)) + '').toFixed(2);
        material['batch_unit'] = "Kg";
      } else {
        material["batch_qty"] = (material["qty"] * this.batch_size).toFixed(2);
        material['batch_unit'] = material['unit'];
      }

        let batch_qty = +material["batch_qty"];
      // material["lot_qty"] = (+batch_qty / this.lots).toFixed(2);
      // material['lot_unit'] = material['batch_unit'];

      packing_materials[i] = material;
    }
    this.selectedResult['packing_materials'] = packing_materials;
  }

  isnewProcedure(id){
    this.id = id;
    this.isProcedureAdd = true;
  }
  isnewInstructionView(id){
    this.id = id;
    this.isInstructionAdd = true;
  }

  isNewEquipment(id) {
    this.id = id;
    this.isEquipmentAdd = true;
  }

  isNewInprocess(id){
    this.id = id;
    this.isInprocessAdd = true;
  }
  
  isNewEnviroment(id){
    this.id = id;
    this.isEnvironmentAdd = true;
  }

  isNewWeighings(id){
    this.id = id;
    this.isWeighingsAdd = true;
  }

  isViewChecklist(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isViewCleaaranceChecklist = true;
  }

  isViewProcedure(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isProcedureView = true; 
  }

  isViewisInstruction(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isInstructionView = true;
  }

  isViewEquipment(index){
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isViewEquipments = true;
  }

  selectedEquipment = [];
  selectedEquipment_data = [];
  selectEquipment(index) {
      this.selectedEquipment = this.equipmentsType[index];

  }
  selectEquipment_data(index) {
      this.selectedEquipment_data = this.eqData[index];

  }
  eqData = [] ;
  eqDatas = [] ;
  addEquipment(index) {
    // let list = this.options1[index].list;
    // list[list.length] = this.selectedEquipment;
    
    // this.selectedEquipment = [];
    let temp={};
    temp['make']=this.selectedEquipment['make'];
    temp['category']=this.selectedEquipment['category'];
    temp['capacity']=this.selectedEquipment['capacity'];
    temp['equipment_name']=this.selectedEquipment['equipment_name'];
    // this.eqData.push(temp);
    this.eqData[this.eqData.length]=temp; 
    // this.equipementListData[this.equipementListData.length]=temp;
      // list[list.length] = temp;
      // this.selectedEquipment=[];
    console.log(temp);
  }

  addEquipment_data(index) {
    // let list = this.options1[index].list;
    // list[list.length] = this.selectedEquipment;
    
    // this.selectedEquipment = [];
    let temp={};
    temp['make']=this.selectedEquipment_data['make'];
    temp['category']=this.selectedEquipment_data['category'];
    temp['capacity']=this.selectedEquipment_data['capacity'];
    temp['equipment_name']=this.selectedEquipment_data['equipment_name'];
    // this.eqData.push(temp);
    this.eqDatas[this.eqDatas.length]=temp; 
    // this.equipementListData[this.equipementListData.length]=temp;
      // list[list.length] = temp;
      // this.selectedEquipment=[];
    console.log(temp);
  }

  ipqcnewForm(){
    this.isnewIpqcForm = true;
  }


  isViewInprocess(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isInprocessView = true;
  }

  isViewEnviroment(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isEnvironmentView = true;
  }

  isViewWeighings(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isWeighingsView = true;
  } 

  addList(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.checkList[this.checkList.length] = temp;
    data.resetForm();
  }

  del(index) {
    this.checkList.splice(index, 1);
  }


  saveInstruction(data){
    if (!data.valid) {
      return;
    }
    let temp = data.value;

    temp['instruction'] = this.instructionList;
    temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveInstruction',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['instructions'] = response['instructions'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }

  delInstruction(index) {
    let instructions = this.selectedStage['instructions'];
    instructions.splice(index, 1);
    this.service.post('production/stages.php?type=deleteInstruction&id=' + this.selectedStage['id'], JSON.stringify(instructions)).subscribe(response => {
      if(response['status']=='success') {
        this.selectedStage['instructions'] = instructions;
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  
  saveEnv(data){
    if (!data.valid) {
      return;
    }
    let temp = data.value;
    temp['checkpoint'] = this.environmentList;
    temp['id'] = this.selectedStage['id'];
    temp['env_frequency'] = this.envfrequency;
    temp['env_freq_unit'] = this.fre_unit;
    temp['env_qa'] = this.env_qa;
    temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveEnvironment',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['environments'] = response['environments'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }



  saveEquipment(data){
    if(!data.valid){
      alertify.error("All Fields Are Required !!");
      return;
    }
    let temp = {};
    // temp = temp[0];
    // let index = 0;
    // this.selectEquipments = this.eqDatas[index];
    temp['equipment'] = this.eqDatas;
    // temp['equipment_name'] = this.selectedEquipment_data['equipment_name'];
    console.log('selectedEquipment_data',this.selectedEquipment_data);
    // temp['id'] = this.selectedStage['id'];
    temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveEquipment',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['equipments'] = response['equipments'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });  

  }

  deleteEquipments(index) {
    let equipments = this.selectedStage['equipments'];
    let equipment_name = equipments['equipment_name'];
    equipment_name.splice(index, 1);
    this.service.post('production/stages.php?type=deleteEquipment&id=' + this.selectedStage['id'], JSON.stringify(equipment_name)).subscribe(response => {
      if(response['status']=='success') {
        this.selectedStage['equipments'] = equipment_name;
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }


  addInprocess(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.inprocessList[this.inprocessList.length] = temp;
    data.resetForm();
  }

  delInprocess(index) {
    this.inprocessList.splice(index, 1);
  }

  addProcedure(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.procedureList[this.procedureList.length] = temp;
    data.resetForm();
  }
  addInstruction(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.instructionList[this.instructionList.length] = temp;
    data.resetForm();
  }


  delProcedure(index) {
    this.procedureList.splice(index, 1);
  }

  addEnv(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.environmentList[this.environmentList.length] = temp;
    data.resetForm();
  }
  getApprovedLabors() {
    this.service.get('common.php?type=getprodOperatorsWorkerList')
    .subscribe(response => {
      this.labors = response;
    });
    console.log(this.labors);
  }

  delEnv(index) {
    this.environmentList.splice(index, 1);
  }

  addWeighings(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.weighingsList[this.weighingsList.length] = temp;
    data.resetForm();
  }

  delWeighings(index) {
    this.weighingsList.splice(index, 1);
  }
  
  addInitialCheck(data) {
    console.log(data.value)
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.initialCheckList[this.initialCheckList.length] = temp;
    data.resetForm();
  }
  

  delInitialCheck(index) {
    this.initialCheckList.splice(index, 1);
  } 

  saveInitialChecks(data){
    if (!data.valid) {
      return;
    }
    let temp = data.value;
    temp['checkpoint'] = this.initialCheckList;
    temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveInitialCheck',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['initial_checks'] = response['initial_checks'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
        data.resetForm();
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }


  saveIPQCCheckpoints(){
    
    let temp = {};
    // temp['ipqc'] = this.selectedStage['ipqccheck'];
    temp['id'] = this.selectedStage['id'];
    this.service.post('production/stages.php?type=',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['ipqc'] = response['ipqc'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }

  
  saveWeighings(data){
    if (!data.valid) {
      return;
    }
    let temp = data.value;
    if(this.isweighing == "NO"){
      temp['weighings'] = '';  
    }else{
      temp['checkpoint'] = this.weighingsList;
    }
    temp['id'] = this.selectedStage['id'];  
    temp['weighing_production'] = this.weighing_production;
    temp['weighing_qa'] = this.weighing_qa;

    this.service.post('production/stages.php?type=saveWeighing',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['weighing'] = response['weighing'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }

  addGeneralList(data) {
    if (!data.valid) {
      return;
    }
    let temp = data.value;
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    this.service.post('production/ebmr.php?type=addInstruction',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        this.selectedResult['instructions'] = response['instructions'];
        data.resetForm();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  deleteGeneralInstruction(index) {
    let instructions = this.selectedResult['instructions'];
    instructions.splice(index, 1);
    this.service.post('production/ebmr.php?type=deleteInstruction&mfr_no=' + this.selectedResult['mfr_no'], JSON.stringify(instructions)).subscribe(response => {
      if(response['status']=='success') {
        this.selectedResult['instructions'] = instructions;
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  addAbbreviationList(data){
    if (!data.valid) {
      return;
    }
    let temp ={};
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    temp['abbreviation'] = data.value;
    this.service.post('production/ebmr.php?type=addAbbreviation',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        this.selectedResult['abbreviation'] = response['abbreviation'];
        data.resetForm();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  deleteAbbreviationList(index) {
    let abbreviation = this.selectedResult['abbreviation'];
    abbreviation.splice(index, 1);
    this.service.post('production/ebmr.php?type=deleteAbbreviation&mfr_no=' + this.selectedResult['mfr_no'], JSON.stringify(abbreviation)).subscribe(response => {
      if(response['status']=='success') {
        this.selectedResult['abbreviation'] = abbreviation;
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
 
  
  addBMRChecks(data){
    let temp ={};
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    temp['bmr_checklist'] = data.value;
    this.service.post('production/ebmr.php?type=saveBMRchecklist',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        this.selectedResult['bmr_checklist'] = response['bmr_checklist'];
        alertify.success(response['msg']);
        data.resetForm();
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  deletebmr(index){
    let bmr_checklist = this.selectedResult['bmr_checklist'];
    bmr_checklist.splice(index, 1);
    this.service.post('production/ebmr.php?type=deletebmrChecklist&mfr_no=' + this.selectedResult['mfr_no'], JSON.stringify(bmr_checklist)).subscribe(response => {
      if(response['status']=='success') {
        this.selectedResult['bmr_checklist'] = bmr_checklist;
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  saveClearance(){
    let temp = {};
    temp['isclerance'] = this.isclerance;
    if(this.isclerance == "NO"){
      temp['clearances'] = '';  
    }else{
      temp['qa_clearances'] = this.checklistList;
      temp['prod_clearances'] = this.prod_checklistList;
    }
    // temp['id'] = this.id;
    temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveClearance',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.iscleranceAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!")
      }
    });
  }





  saveInprocess(data){
    let temp = data.value;
    if(this.ischeck == "NO"){
      temp['checks'] = '';  
    }else{
      temp['checks'] = this.inprocessList;
    }
    temp['id'] = this.id;
    this.service.post('production/stages.php?type=saveCheck',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.isInprocessAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }




  addIntegrity(data) {
    console.log(data.value)
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.integrityCheckList[this.integrityCheckList.length] = temp;
    data.resetForm();
  }
  delIntegrity(index) {
    this.integrityCheckList.splice(index, 1);
  } 
  ScreenCheckList=[];
  addScreen(data) {
    console.log(data.value)
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.ScreenCheckList[this.ScreenCheckList.length] = temp;
    data.resetForm();
  }
  delScreen(index) {
    this.ScreenCheckList.splice(index, 1);
  } 

  
  
  saveProcedure(data){
    let temp = data.value;
    if(this.isprocedure == "NO"){
      temp['procedures'] = '';  
    }else{
      temp['procedures'] = this.procedureList;
    
    }
    temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveProcedure',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }

  saveScreen(data){
    let temp = data.value;

      temp['Screen'] = this.ScreenCheckList;
    
      temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveScreenCheck',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.isInprocessAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveSieve(data){
    let temp = data.value;

      temp['Sieve'] = this.integrityCheckList;
    
      temp['id'] = this.S_stage['id'];
    this.service.post('production/stages.php?type=saveSieveCheck',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.isInprocessAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }

  saveStage(data){
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['product_code'] = this.selectedResult['product_code'];
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    this.service.post('production/stages.php?type=newStage',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        data.resetForm();
        this.isNewStage = false;
        this.newStageMaster = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }


}
