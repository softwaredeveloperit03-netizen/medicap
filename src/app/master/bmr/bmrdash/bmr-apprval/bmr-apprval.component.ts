import { Component, OnInit,AfterViewInit  } from '@angular/core';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DataService } from '../data.service';

declare let alertify;
@Component({
  selector: 'app-bmr-apprval',
  templateUrl: './bmr-apprval.component.html',
  styleUrls: ['./bmr-apprval.component.css']
})
export class BmrApprvalComponent implements OnInit {

  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router,private dataService: DataService) { }
  selectedResult: any;
  batch_size = 100000;
  options2 = [
    { "name": "Procedure","option": "Procedure", "status": false, "list": []},
    { "name": "Instruction","option": "Instruction", "status": false, "list": []}
  ];
  ngOnInit(): void {

    
    // this.route.paramMap.subscribe(params => {
    //   this.getProcessStage(params.get('id'));
    // });


     
    this.route.paramMap.subscribe(params => {
      this.getProcessStage(params.get('id'));
    });
    this.selectedResult = this.dataService.getData();
    console.log('Service data found:', this.selectedResult);


    if(this.selectedResult==undefined){
      this.router.navigate(['..', '..'], { relativeTo: this.route })

    }
 
    // this.getProcessStage(this.selectedResult['manufacturing_process_id']);
    
    this.getInstrunctions();
    this.getAbbrivation();
   this.getbmrEquipments();
   this.getbmrRooms();
   this.getProcedure();
  }
 
  stagemaster;
Stages;

  getProcessStage(id){  
    this.service.get('bmr/process.php?type=getProcesses_view&id='+id).subscribe(response=>{
      this.stagemaster=response;
      this.Stages=this.stagemaster[0]['Stages']
 
    });

}
instructions;
getInstrunctions(){
  this.service.get('production/ebmr.php?type=get_Instruction&manufacturing_process_id=')
  .subscribe(response =>{
    this.instructions=response;
  });
}
abbreviation;
getAbbrivation(){
  this.service.get('production/ebmr.php?type=getAbbrivation&manufacturing_process_id=')
  .subscribe(response =>{
    this.abbreviation=response;
  });
}
eqDatas;
getbmrEquipments(){
  this.service.get('production/ebmr.php?type=getbmrEquipments&manufacturing_process_id=')
  .subscribe(response =>{
    this.eqDatas=response;
  });
}
bmrRoomDatas
getbmrRooms(){
  this.service.get('production/ebmr.php?type=getbmrRooms&manufacturing_process_id=')
  .subscribe(response =>{
    this.bmrRoomDatas=response;
  });
}
Proceduresss;
getProcedure(){
  this.service.get('production/ebmr.php?type=get_Procedure&manufacturing_process_id=')
  .subscribe(response =>{
    this.Proceduresss=response;
  });
}
isEncapsule=false;
selectedPage = 0;
stepsss=false;
selectPage(index, index1) {
  this.selectedPage = index;
  if(index==13){
    this.isEncapsule=true;
    console.log('hellllo')
  }
 
  this.stepsss=false;
   
}
selectedStage = [];
selectStagePage(index) {
  this.selectedPage = 99;
 
    this.selectedStage = this.Stages[index]; 
  this.stepsss=true
  
   console.log(this.stepsss);
  
   
}

isprocedure;
isroom;
isequipment;
isCleaningChecks;
isroomActions;
isEquipmemntCleaning;
isLogbook;
istable;
isweighing;
isQcSample;
isFraction
isFormats
isQaReview

selected_step=[];
isNewStage2=false;

procedures:any=[];
form_no_list:any=[];
room:any=[];
equipmentsss:any=[];
weighings:any=[];
CleaningChecks:any=[];
roomActions:any=[];
EquipmemntCleaning:any=[];
tables:any=[];
QcSample:any=[];
Formats:any=[];
QaReview:any=[];

steps2;

seq_list=[];
isNewStages2(index){

  this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isweighing='';
  this.isQcSample='';


 


  this.selected_step=this.selectedStage['Steps'][index]
  this.isNewStage2 = true;

  this.service.get('production/stages.php?type=GET_bmr_stages_steps&stpe_id='+this.selected_step['id']+'&Stage_id='+this.selectedStage['id']).subscribe(response =>{
    this.steps2 =response;
    //  this.instruction=JSON.parse(this.steps2[0]['instructions']);
     this.procedures=this.steps2[0]['procedures'];
     this.form_no_list=this.steps2[0]['Logbook'];
     this.room=this.steps2[0]['room'];
     this.equipmentsss=this.steps2[0]['equipment'];
     this.weighings=this.steps2[0]['weighing'];
     this.CleaningChecks=this.steps2[0]['CleaningChecks'];
     this.roomActions=this.steps2[0]['roomActions'];
     this.EquipmemntCleaning=this.steps2[0]['EquipmemntCleaning'];
     this.tables=this.steps2[0]['tables'];
     this.QcSample=this.steps2[0]['qcSamples'];
     this.Formats=this.steps2[0]['Formats'];
     this.QaReview=this.steps2[0]['QaReview'];
     console.log(this.equipmentsss)
     
     
     // for dd
     this.isprocedure=this.steps2[0]['isprocedure'];
     this.isroom=this.steps2[0]['isroom'];
     this.isequipment=this.steps2[0]['isequipment'];
     this.isCleaningChecks=this.steps2[0]['isCleaningChecks'];
     this.isroomActions=this.steps2[0]['isroomActions'];
     this.isEquipmemntCleaning=this.steps2[0]['isEquipmemntCleaning'];
     this.istable=this.steps2[0]['istable'];
     this.isFraction=this.steps2[0]['isFraction'];
     this.isweighing=this.steps2[0]['isweighing'];
     this.isQcSample=this.steps2[0]['isQcSample'];
    //  this.isQcSample=this.steps2[0]['isQcSample'];
     this.isFormats=this.steps2[0]['isFormats'];
     this.isQaReview=this.steps2[0]['isQaReview'];

     console.log(this.isprocedure)
     console.log(this.isroom)
     console.log(this.isequipment)
     console.log(this.isCleaningChecks)
     console.log(this.isroomActions)
     console.log(this.isEquipmemntCleaning)
     console.log(this.istable)
     console.log(this.isFraction)
     console.log(this.isweighing)
     console.log(this.isQcSample)
     console.log(this.isFormats)
     console.log(this.isQaReview)





     
     //  sequence
     this.seq_list=JSON.parse(this.steps2[0]['sequence']);
    //  console.log(this.sequence)
  });
  
}



bmr_rooms;

selectedbmr_rooms=[];
selectedbmr_rooms2=[];
getBmrRoomDetails(index){
  this.selectedbmr_rooms=this.bmr_rooms[index-1]
 console.log(this.selectedbmr_rooms);
}
getBmrRoomDetails2(index){
  this.selectedbmr_rooms2=this.bmrRoomDatas[index-1]
 console.log(this.selectedbmr_rooms2);
}
selectedbmr_rooms22=[];
getBmrRoomDetails22(index){
  this.selectedbmr_rooms22=this.bmrRoomDatas[index-1]
 console.log(this.selectedbmr_rooms22);
}
 
selectedEquipment_data2=[];
getEquipmentDetails2(index){
  this.selectedEquipment_data2=this.eqDatas[index-1]
  console.log(this.selectedEquipment_data2);
 
}
bmrRoomList=[];
 
bmractionRoomList=[];
 
CleaStatusList=[];
 
 
 
eqdatas2=[]; 

selectEquipments =[];
stage_selected;
 
newStageMaster=false;
stageMaster(){
  this.newStageMaster = true;
}

 
ProcessTitle;
DocumentNo;
Forms;

 

// getProcesses(){
//   this.service.get('bmr/process.php?type=getProcesses').subscribe(response =>{
//     this.stagemaster =response;
    
//   });
  
// }
// getProcesses(){
//   this.service.get('bmr/process.php?type=getProcesses').subscribe(response =>{
//     this.stagemaster =response;
//   });
// }
sections;
getSection(){
  this.service.get('common.php?type=getDepartmentSections&department1=Production').subscribe(response =>{
    this.sections =response;
  });
}
selectedstep=[];
selectedsteps=[];
selectedStages=[];
stepssss(index){
  // index = index +1
 this.selectedStages=this.selectedResult['Stages'][index];
 this.selectedsteps=this.selectedStages['steps'];
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
selectStage = [];
getStageMasterDetails(index){
 
    this.selectStage = this.stagemaster[index];
    this.select_Stage = this.selectStage['process_types'];

}
S_stage;
selectedStageIndex = -1;

 
 

islessthan=false;
ismorethan=false;
isrange=false;

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
 
 

isViewCleaaranceChecklist= false;
isProcedureView= false;
isInstructionView= false;
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
 }

selectedEquipment = [];
selectedEquipment_data = [];
 
selectEquipment_data(index) {
    this.selectedEquipment_data = this.eqData[index];

}
eqData = [] ;
 
eqDataList=[];


isWeighingsView=false;
isInprocessView=false;
isnewIpqcForm=false;

ipqcnewForm(){
  this.isnewIpqcForm = true;
}


isViewInprocess(index) {
  let stages = this.selectedResult['stages'];
  this.selectedStage = stages[index];
  this.isInprocessView = true;
}

 

isViewWeighings(index) {
  let stages = this.selectedResult['stages'];
  this.selectedStage = stages[index];
  this.isWeighingsView = true;
} 

 


 
 
 
RoomClearanceCheckList;
Form_no;
version_no;
specification_no;
effective_date;
  
getRoomChecklist() {
  this.service.get('production/stages.php?type=getRoomChecklist')
  .subscribe(response => {
    this.RoomClearanceCheckList = response;
  });
 }
LineClearanceCheckList;
getLineChecklist() {
  this.service.get('production/stages.php?type=getLineChecklist')
  .subscribe(response => {
    this.LineClearanceCheckList = response;
  });
 }
 
RoomDatas;
getbmrRoom(){
  this.service.get('production/ebmr.php?type=addbmrRooms&manufacturing_process_id='+this.selectedResult['id'])
  .subscribe(response =>{
    this.RoomDatas=response;
  });
}

 

 
sequnce;
 combinedList:any=[];
 
 

 
 
 
stage_lists: any[] = [];

 

 
 isNewStage=false;
isNewStages(index){
this.isNewStage = true;
this.selected_step=this.selectedStage['Steps'][index]
}
isNewdesp=false;
isDesp(){
this.isNewdesp = true;

}
 
steps22;
instruction;
 Qa_LINE_clearance;
prod_LINE_clearance;
 environmentsss;
 initial_checks;
inprocess;
sequence;

 
 
isinitial;
isinprocess;


 
 

 
 
deleteGeneralInstruction1(instructionIndex: number, sublistIndex: number) {
  // Assuming stage_lists is a property in your component containing the data
  // Remove the instruction from the sublist using splice
  this.stage_lists[sublistIndex].splice(instructionIndex, 1);
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


 
 
 
 
 
 
 


 

 
stepss=[];
step

addsteps(data) {
  if (!data.valid) {
    alert('All fields are required');
    return;
  }
  let temp = data.value;
 
  this.stepss[this.stepss.length] = temp;
  console.log(this.stepss);
  // data.resetForm();
  
 
  this.step='';
}

delstepsjadu(index){
  this.stepss.splice(index,1);
}
delprossesStep(index){
  this.stepss.splice(index,1);
}





for_department;
stage;
 


 









completeMaster(status){

 
  let temp ={};
      temp['product_code']=this.selectedResult['product_code']
      temp['status']=status;
      temp['stages']=this.Stages;
  this.service.post('bmr_new/bmr.php?type=complete_bmr_master',JSON.stringify(temp)).subscribe(response =>{
    if(response['status']=='success') {
      this.router.navigate(['..', '..'], { relativeTo: this.route })
    
      alertify.success('SAVE');
    } else{
      alertify.error(response['msg']);
    }
  });
}










}
