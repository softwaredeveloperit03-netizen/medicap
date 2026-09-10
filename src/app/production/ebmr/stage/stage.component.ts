import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-stage',
  templateUrl: './stage.component.html',
  styleUrls: ['./stage.component.css']
})
export class StageComponent implements OnInit {
  selectedEquipments = [];
  results: any = [];
  isView = false;
  id;
  isrange =false;
  water;
  envfrequency ='';
  islessthan =false;
  ismorethan = false;
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
  
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getManufacturingStages();
    /* this.getsampleWashWater();
    this.getEquipmentDetails();
    this.getProcesses();
    this.getDosages();
    this.getSpecification(); */
    this.getUnits();
    this.getIPQC();
    this.getProcesses();
  }
  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response=>{
      this.units = response;
    });
  }


  sheet(){
    this.isTISheet = true;
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


  viewwater(index){
    this.selectedwater =this.water[index];
    this.selewater = true;
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


  getStageMasterDetails(index){
    index = index -1;
    if (index !== -1) {
      this.selectStage = this.stagemaster[index];
      
    } else {
      this.selectStage = [];
    }
    
  }

  selectedStageIndex = -1;
  selectPage(index, index1) {
    this.selectedPage = index;
    if (index > 9 && index1 !== -1) {
      let stages = this.selectedResult['stages'];
      this.selectedStage = stages[index1];
      this.selectedStageIndex = index;
    } else {
      this.selectedStage = [];
    }
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
  selectEquipment(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEquipment = this.equipmentsType[index];
    } else {
      this.selectedEquipment = [];
    }
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


  addInstruction(data){
    if (!data.valid) {
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedStage['id'];
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
    let index = 0;
    this.selectEquipments = this.equipments[index];
    temp['equipment_type'] = this.selectEquipments['equipment_type'];
    temp['equipment_name'] = this.selectEquipments['equipment_name'];
    console.log('selectEquipments',this.selectEquipments);
    temp['id'] = this.selectedStage['id'];
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
    temp['id'] = this.selectedStage['id'];
    this.service.post('production/stages.php?type=saveInitialCheck',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
        let stages = this.selectedResult['stages'];
        let stage = stages[this.selectedStageIndex];
        stage['initial_checks'] = response['initial_checks'];
        stages[this.selectedStageIndex] = stage;
        this.selectedResult['stages'] = stages;
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
      temp['clearances'] = this.checkList;
    }
    temp['id'] = this.id;
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

  
  
  saveProcedure(data){
    let temp = data.value;
    if(this.isprocedure == "NO"){
      temp['procedures'] = '';  
    }else{
      temp['procedures'] = this.procedureList;
    }
    temp['id'] = this.id;
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

  saveSieve(){

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
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }


}
