import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DataService } from '../data.service'
declare let alertify;

@Component({
  selector: 'app-bmr',
  templateUrl: './bmr.component.html',
  styleUrls: ['./bmr.component.css']
})
export class BmrComponent implements OnInit {
purpose;
product_code;
Process_Title;
tables:any=[];
isMain=true;

closeOffcanvas(): void {
  this.isNewStage2 = false;
}



  selectedEquipments = [];
  results: any = [];
  isView = false;
  isStart = false;
  isReview = false;
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
  LineClearance:any=[];
LineClearanceList=[]
  initialCheckList=[];
  integrityCheckList=[];
  generalIntegrity=[];
  selectStage = [];
  ipqc;
  selectedIndex = -1;
  frequency;
  isclerance='';
  isinstruction='';
  ischeck='';
  isProduction='';
  isQa='';
  isQc='';
  env_production='';
  env_qa='';
  isenvironment = '';
  isweighing= '';
  isDrying= '';
  isBlendLubrication= '';
  isWeighing_Variation_Recoed= '';
  isCOMPRESSION_PARAMETERS= '';
  isINPROCESS_YIELD= '';
  isYIELD_RECONCILIATION= '';
  isQcSample;

  weighing_production='';
  weighing_qa='';
  isprocedure;
  isNewStage=false;
  selectedPage = 0;
  selectedwater=[];
  selewater = false;
  equipmentsType;
  batch_size = 100000;
  isTISheet = false;
  newStageMaster = false;
  fre_unit = '';


  stagemaster;
  processes = [];
  dosage_form = '';
  process_type = '';
  dosages;
  bmrList = [];
  spec;
  selectSpec;
  fg_sub_materials;
  ipqccheck;
  plant_type;
  MainselectedStageIndex=-1;
  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router,private dataService: DataService) {
    this.plant_type = this.service.getPlantConfigFields('plant_type');

  }

  ngOnInit() {
    this.getManufacturingStages();
this.getDosageTypes();
    console.log(this.labors);
  }
  getDosageTypes() {
  this.service.get('master/product.php?type=get_dosage_typesBMRMaster').subscribe(response => {
    this.fg_sub_materials = response;
  });
}
products;
StagesData;
  getProductsByDosage(value) {
    this.service.get('master/product.php?type=getProductsByDosageForm&product_type=' + value).subscribe(response => {
      this.products = response;
    });
    this.getProcessTypes(value)

  }
    getProcessTypes(value){
    this.service.get('bmr/process.php?type=getProcessesmaster&dosage_form='+value).subscribe(response=>{
      this.StagesData = response;
    });
  }
  selected_Stage=[]
  selectedProcesstype:any=[]
  getStages(index){
    this.selectedProcesstype=this.StagesData[index-1];
    this.selected_Stage=this.selectedProcesstype['stages'];
  }
  getManufacturingStages() {

    this.service.get('bmr_new/product.php?type=getBrandProductsLogBMR').subscribe(response=>{
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.start(this.selectedIndex);
        if(this.MainselectedStageIndex!==-1){
          console.log('this.MainselectedStageIndex :>> ', this.MainselectedStageIndex);
          this.selectStagePage(this.MainselectedStageIndex)

        }

      }
    });
  }
  stage_index


  start(index){
    // this.selectedwater =this.water[index];
    // if(this.processes1.length==0){
      this.selectedIndex = index;
      this.selectedResult = this.results[index];
      this.isStart = true;
      console.log(this.selectedResult)

   this.getProcessStage(this.selectedResult['process_id']);

  }
  Stages;
  stageDaysLoading = false;
  stageDaysSaved = false;

  getProcessStage(id){

    this.service.get('bmr_new/process.php?type=getProcesses&id='+id).subscribe(response=>{
      this.stagemaster=response;
      this.Stages=this.stagemaster[0]['Stages'];
      this.loadProductStageDays();
    });
  }

  loadProductStageDays() {
    const pc = this.selectedResult && this.selectedResult['product_code'];
    if (!pc || !this.Stages || !this.Stages.length) { return; }
    this.stageDaysLoading = true;
    this.service.get('master/product.php?type=getProductStagesWithDays&product_code=' + encodeURIComponent(pc)).subscribe({
      next: (stagesWithDays: any[]) => {
        const list = Array.isArray(stagesWithDays) ? stagesWithDays : [];
        const byId = {};
        list.forEach((s: any) => { byId[s.stage_id || s.id] = (s.days != null ? Number(s.days) : 0); });
        (this.Stages || []).forEach((st: any) => { st.days = byId[st.id] != null ? byId[st.id] : 0; });
        this.stageDaysLoading = false;
      },
      error: () => { this.stageDaysLoading = false; }
    });
  }

  saveStageDays() {
    const pc = this.selectedResult && this.selectedResult['product_code'];
    if (!pc || !this.Stages || !this.Stages.length) {
      alertify.warning('No product or stages selected.');
      return;
    }
    const stages = this.Stages.map((st: any) => ({
      stage_id: st.id,
      id: st.id,
      days: st.days != null ? Number(st.days) : 0
    }));
    const payload = { product_code: pc, stages };
    this.service.post('master/product.php?type=updateProductStageDays', JSON.stringify(payload)).subscribe({
      next: (res: any) => {
        if (res && res.status === 'success') {
          alertify.success('Stage days saved.');
          this.stageDaysSaved = true;
        } else {
          alertify.error(res && res.message ? res.message : 'Save failed.');
        }
      },
      error: () => { alertify.error('Save failed.'); }
    });
  }

  selectedStage = [];
stepsss = false;
isNewStages2Index=-1;
isNewStage2 = false;
steps2;
selected_step=[];
NewStages2Step
selectStagePage(index) {

  this.stage_index=index+3
  this.selectedPage = 99;
  this.MainselectedStageIndex=index;
    this.selectedStage = this.Stages[index];

  this.stepsss=true

   console.log(this.stepsss);
   console.log('Cheking :>> ', );

   if(this.isNewStages2Index!==-1){
    console.log('this.isNewStages2Index :>> ', this.isNewStages2Index);
    this.isNewStages2(this.isNewStages2Index,this.NewStages2Step)
  }

}
isNewStages2(index,step){
  this.isNewStages2Index=index;
  this.isprocedure='';

  this.NewStages2Step=step;

  this.isStart = false;


  this.selected_step=this.selectedStage['Steps'][index]
  this.isNewStage2 = true;


  this.service.get('bmr_new/stages.php?type=GET_bmr_stages_steps&stpe_id='+this.selected_step['id']+'&Stage_id='+this.selectedStage['id']).subscribe(response =>{
    this.steps2 =response;

  });

}
  Review(id: string, index: number) {
    const selectedResult = this.results[index];
    this.dataService.setData(selectedResult);

      this.router.navigate(['/master/bmr-master/bmrdash/BmrReview', id]);


  }

  Approval(id: string, index: number){
    const selectedResult = this.results[index];
    this.dataService.setData(selectedResult);


      this.router.navigate(['/master/bmr-master/bmrdash/Bmrapproval', id]);

  }
  // bmr_view(id: string, index: number){
  //   const selectedResult = this.results[index];
  //   this.dataService.setData(selectedResult);


  //     this.router.navigate(['/prod-f-ebmr/ebmr/bmr_dash/BmrView', id]);

  // }
  bmr_view(id: string)
  {
    this.service.open('bmr_new/bmr.php?type=masterBMR&id='+id);
    // console.log(this.selectedOrder['id']);
  }
  completeMaster(status){
    let temp ={};
        temp['product_code']=this.selectedResult['product_code']
        temp['dosage_form']=this.selectedResult['dosage_form']
        temp['DocumentTitle']=this.selectedResult['DocumentTitle']
        temp['DocumentNo']=this.selectedResult['DocumentNo']
        temp['status']=status;
        temp['stages']=this.Stages;

    this.service.post('bmr_new/bmr.php?type=complete_bmr_masterZuma',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        this.router.navigate(['/master'])

        alertify.success('SAVE');
      } else{
        alertify.error(response['msg']);
      }
    });
  }


// Add these at the top of your component
undoStack: any[] = [];
redoStack: any[] = [];

// Delete with history tracking
deleteStage(index: number) {
  const deletedStage = this.Stages[index];

  // Save current state to undo stack
  this.undoStack.push({
    action: 'delete',
    index,
    stage: deletedStage
  });

  // Clear redo stack on new action
  this.redoStack = [];

  // Perform deletion
  this.Stages.splice(index, 1);
  this.selectedStage = [...this.Stages];
}

// Undo the last action
undo() {
  if (this.undoStack.length === 0) return;

  const lastAction = this.undoStack.pop();

  if (lastAction.action === 'delete') {
    this.Stages.splice(lastAction.index, 0, lastAction.stage);
    this.selectedStage = [...this.Stages];

    // Push to redo stack
    this.redoStack.push(lastAction);
  }
}

// Redo the last undone action
redo() {
  if (this.redoStack.length === 0) return;

  const lastUndo = this.redoStack.pop();

  if (lastUndo.action === 'delete') {
    this.Stages.splice(lastUndo.index, 1);
    this.selectedStage = [...this.Stages];

    // Push back to undo stack
    this.undoStack.push(lastUndo);
  }
}

  // deleteStage(index){
  //   this.Stages.splice(index, 1);
  //   this.selectedStage = this.Stages;
  // }
  stepUndoStack: any[] = [];
stepRedoStack: any[] = [];

substepUndoStack: any[] = [];
substepRedoStack: any[] = [];

  // deleteStage_Step(index) {
  //   this.selectedStage['steps'].splice(index, 1);
  // }
  // deleteStage_Step_Substep(index, subIndex) {
  //   this.selectedStage['steps'][index].Substeps.splice(subIndex, 1);
  // }

  deletionHistory: any[] = [];
  deletionRedoStack: any[] = [];
  removeStageItem(target: 'step' | 'substep', stageIdx: number, subIdx?: number) {
    if (target === 'step') {
      const deletedStep = { ...this.selectedStage['steps'][stageIdx] };
      this.deletionHistory.push({ target, stageIdx, data: deletedStep });
      this.selectedStage['steps'].splice(stageIdx, 1);
    } else if (target === 'substep' && subIdx !== undefined) {
      const deletedSub = { ...this.selectedStage['steps'][stageIdx].Substeps[subIdx] };
      this.deletionHistory.push({ target, stageIdx, subIdx, data: deletedSub });
      this.selectedStage['steps'][stageIdx].Substeps.splice(subIdx, 1);
    }

    this.deletionRedoStack = [];
  }
  undoRemoval() {
    const lastAction = this.deletionHistory.pop();
    if (!lastAction) return;

    if (lastAction.target === 'step') {
      this.selectedStage['steps'].splice(lastAction.stageIdx, 0, lastAction.data);
    } else if (lastAction.target === 'substep') {
      this.selectedStage['steps'][lastAction.stageIdx].Substeps.splice(lastAction.subIdx, 0, lastAction.data);
    }

    this.deletionRedoStack.push(lastAction);
  }
  redoRemoval() {
    const lastUndone = this.deletionRedoStack.pop();
    if (!lastUndone) return;

    if (lastUndone.target === 'step') {
      this.selectedStage['steps'].splice(lastUndone.stageIdx, 1);
    } else if (lastUndone.target === 'substep') {
      this.selectedStage['steps'][lastUndone.stageIdx].Substeps.splice(lastUndone.subIdx, 1);
    }

    this.deletionHistory.push(lastUndone);
  }

DocumentNo;
ProcessTitle;
ispopup = false;
  saveProcess(){
    let temp={};
    temp['product_code']=this.product_code;
    temp['DocumentNo']=this.DocumentNo;
    temp['ProcessTitle']=this.ProcessTitle;
    temp['stages']=this.selected_Stage;
    this.service.post('bmr/process.php?type=saveProcess', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Processes Saved Successfully');
        this.dosage_form = '';
        this.processes = [];
        this.ispopup = false;
        this.getManufacturingStages();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
