import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;



@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  dosages;
  /* types = [
    {"name": "instruction", "label": "General Instructions", "status": false},
    {"name": "line_clearance", "label": "Line Clearance", "status": false},
    {"name": "procedure", "label": "Procedures", "status": false},
    {"name": "equipment", "label": "Equipments", "status": false},
    {"name": "weighing", "label": "Weighing of Material", "status": false},
    {"name": "ischeck", "label": "Inprocess Checks", "status": false},
    {"name": "isenvironment", "label": "Environmental Checks", "status": false},
    {"name": "istesting", "label": "Inprocess Testing", "status": false},
    {"name": "reconciliation", "label": "Reconciliation", "status": false},
  ];

  types1 = [
    {"name": "instruction", "label": "General Instructions", "status": false},
    {"name": "line_clearance", "label": "Line Clearance", "status": false},
    {"name": "procedure", "label": "Procedures", "status": false},
    {"name": "equipment", "label": "Equipments", "status": false},
    {"name": "weighing", "label": "Weighing of Material", "status": false},
    {"name": "ischeck", "label": "Inprocess Checks", "status": false},
    {"name": "isenvironment", "label": "Environmental Checks", "status": false},
    {"name": "istesting", "label": "Inprocess Testing", "status": false},
    {"name": "reconciliation", "label": "Reconciliation", "status": false},
  ]; */

  processes = [];
  dosage_form = '';
  process_type = '';
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDosages();
    this.get_staps();
  }




  steps_data;

  get_staps() {
    this.service.get('master/product.php?type=get_stages').subscribe(response => {
      this.steps_data = response;
    });
  }

  addStage = false;

  addStages(value){

    if(value == 'ADD NEW'){
      this.addStage = true;
    }

  }

  SaveStage(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('master/product.php?type=saveStages', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stage Saved!!!');
        this.get_staps();
        this.addStage = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }





  getDosages() {
    this.service.get('master/product.php?type=get_dosage_typesMFR').subscribe(response => {
      this.dosages = response;
    });
  }



  add(data) {
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    this.processes[this.processes.length] = temp;
    data.resetForm();

  }
  processesSteps: any[] = []; // Empty array, can be populated later

  addStepForm(data) {
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['Substeps'] = this.processes;

    this.processesSteps[this.processesSteps.length] = temp;
    data.resetForm();
    console.log('this.processesSteps :>> ', this.processesSteps);
    this.processes=[];
  }
  forms;
  stageprocesses=[];
  forms_list=[];

  add1(){

    if (Array.isArray(this.forms)) {
      this.forms = this.forms.map(forms => ({
        forms: forms
      }));
    }

    // Add the transformed data to checklistList
    this.forms_list.push(this.forms);



    let temp={}
    temp['stage']=this.stage;
    // temp['forms']=this.forms_list[0];
    temp['steps']=this.processesSteps;
    this.stageprocesses[this.stageprocesses.length] = temp;
    // this.forms_list=[];

  console.log('this.stageprocesses :>> ', this.stageprocesses);
    this.processes=[];
    this.processesSteps=[];

  }
  getProcesses(){
    this.service.get('bmr/process.php?type=getProcesses').subscribe(response=>{
      this.results=response;
    });
  }
  stage;
  saveProcess() {
    // for (let i = 0; i < this.processes.length; i++) {
    //   this.processes[i].dosage_form = this.dosage_form;
    //   this.processes[i].process_type = this.process_type;
    // }
    let temp={};
    temp['dosage_form']=this.dosage_form;
    temp['process_type']=this.process_type;
    temp['stage']=this.stageprocesses;
    this.service.post('bmr/process.php?type=saveProcessmasterZuma&process_type='+this.process_type+'&dosage_form='+this.dosage_form, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Manufacturing Processes Saved Successfully');
        this.router.navigate(['/production/ebmr/process']);
        this.dosage_form = '';
        this.processes = [];
        this.stageprocesses = [];
        this.stage = '';
        this.process_type = '';
        } else {
        alert(response['status']);
      }
    });
  }

  addProcedureList=[];
  Procedure;
  addProcedure(){
    let temp={};
    temp['Procedure']=this.Procedure
    this.addProcedureList.push(temp);
    this.Procedure='';
    console.log('this.addProcedureList :>> ', this.addProcedureList);
  }


  undoStack = []; // Stack to keep track of undone actions
  redoStack = []; // Stack to keep track of redone actions

  // Function to delete a substep in processesSteps
  // deleteStepSubstep(i: number, j: number) {
  //   const step = this.processesSteps[i];

  //   // Check if the Substep array exists and if it's empty
  //   if (step?.Substep && step.Substep.length > 0) {
  //     // If the Substep array has elements, delete the specific substep
  //     const deletedItem = step.Substep[j];
  //     this.undoStack.push({
  //       type: 'substep',
  //       index: i,
  //       subIndex: j,
  //       item: deletedItem,
  //     });
  //     step.Substep.splice(j, 1);
  //     this.redoStack = []; // Clear redo stack after a new action
  //   } else if (step?.Substep && step.Substep.length === 0) {
  //     // If the Substep array is empty, delete the entire step
  //     const deletedItem = this.processesSteps[i];
  //     this.undoStack.push({
  //       type: 'step',
  //       index: i,
  //       item: deletedItem,
  //     });
  //     this.processesSteps.splice(i, 1);
  //     this.redoStack = []; // Clear redo stack after a new action
  //   } else {
  //     // Log a message if Substep is undefined or invalid index
  //     console.warn('Substep array is empty or invalid index', {
  //       processesSteps: this.processesSteps,
  //       index: i,
  //       subIndex: j,
  //     });
  //   }
  // }
  deleteStepSubstep(i: number, j?: number) {
    const stageProcess = this.processesSteps[i];

    // If no substeps exist, delete the entire step
    if (!stageProcess || !stageProcess.Substep || stageProcess.Substep.length === 0) {
      // Push the entire step to undo stack before deletion
      const deletedStep = this.processesSteps.splice(i, 1)[0];
      this.undoStack.push({
        type: 'step',
        index: i,
        item: deletedStep,
      });
      this.redoStack = []; // Clear redo stack after a new action
      return;
    }

    // Otherwise, delete the specific substep if the index is valid
    if (j !== undefined && stageProcess.Substep[j]) {
      const deletedSubstep = stageProcess.Substep.splice(j, 1)[0];
      this.undoStack.push({
        type: 'substep',
        index: i,
        stepIndex: j,
        item: deletedSubstep,
      });
      this.redoStack = []; // Clear redo stack after a new action
    } else {
      console.warn('Invalid substep index or substeps empty');
    }

    // If the Substep array is empty after deletion, remove the whole step
    if (stageProcess.Substep.length === 0) {
      const deletedStep = this.processesSteps.splice(i, 1)[0];
      this.undoStack.push({
        type: 'step',
        index: i,
        item: deletedStep,
      });
      this.redoStack = []; // Clear redo stack after a new action
    }
  }


  deleteStageSubstep(stageIndex: number, stepIndex: number, substepIndex?: number): void {
    const stage = this.stageprocesses[stageIndex];
    if (!stage) return;

    // Save current state for undo
    const snapshot = JSON.parse(JSON.stringify(this.stageprocesses));
    this.undoStack.push(snapshot);
    this.redoStack = [];

    if (substepIndex !== undefined) {
      const step = stage.steps[stepIndex];
      if (!step || !Array.isArray(step.Substeps)) return;

      // Safety check before deletion
      if (substepIndex >= 0 && substepIndex < step.Substeps.length) {
        step.Substeps.splice(substepIndex, 1);

        // Delete the step only if it's empty now
        if (step.Substeps.length === 0) {
          stage.steps.splice(stepIndex, 1);
        }
      }
    } else {
      // If substepIndex is not provided, delete the whole step
      if (stepIndex >= 0 && stepIndex < stage.steps.length) {
        stage.steps.splice(stepIndex, 1);
      }
    }

    // If after all this, stage has no steps left, remove the stage
    if (stage.steps.length === 0) {
      this.stageprocesses.splice(stageIndex, 1);
    }
  }


  undo(): void {
    if (this.undoStack.length > 0) {
      const lastState = this.undoStack.pop();
      const currentState = JSON.parse(JSON.stringify(this.stageprocesses));
      this.redoStack.push(currentState);
      this.stageprocesses = JSON.parse(JSON.stringify(lastState));
    }
  }

  redo(): void {
    if (this.redoStack.length > 0) {
      const nextState = this.redoStack.pop();
      const currentState = JSON.parse(JSON.stringify(this.stageprocesses));
      this.undoStack.push(currentState);
      this.stageprocesses = JSON.parse(JSON.stringify(nextState));
    }
  }




}
