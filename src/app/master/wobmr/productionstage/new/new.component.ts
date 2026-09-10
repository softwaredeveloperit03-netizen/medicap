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
  dosage_form = '';
  process_type = '';
  step = '';
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDosages();
    
  }




 



  getDosages() {
    this.service.get('common.php?type=get_Product_subtype').subscribe(response => {
      this.dosages = response;
    })
    
  }



  processesSteps: any[] = [];

  addStepForm(data) {
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    const temp = { ...data.value, Substeps: [] };
    this.processesSteps.push(temp);
    data.resetForm();
    this.step = '';
  }
  stageprocesses = [];
  stage = '';

  add1() {
    const temp = { stage: this.stage, steps: this.processesSteps };
    this.stageprocesses.push(temp);
    this.processesSteps = [];
  }

  saveProcess() {
    // for (let i = 0; i < this.processes.length; i++) {
    //   this.processes[i].dosage_form = this.dosage_form;
    //   this.processes[i].process_type = this.process_type;
    // }
    let temp={};
    temp['dosage_form']=this.dosage_form;
    temp['process_type']=this.process_type;
    temp['stage']=this.stageprocesses;
    temp['from']='WO BMR';

    this.service.post('bmr/process.php?type=saveProcessmasterZuma&process_type='+this.process_type+'&dosage_form='+this.dosage_form, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Manufacturing Processes Saved Successfully');
        this.router.navigate(['/production/ebmr/process']);
        this.dosage_form = '';
        this.stageprocesses = [];
        this.processesSteps = [];
        this.stage = '';
        this.process_type = '';
        } else {
        alert(response['status']);
      }
    });
  }

 


  undoStack = []; // Stack to keep track of undone actions
  redoStack = []; // Stack to keep track of redone actions
 
  deleteStep(i: number) {
    if (i >= 0 && i < this.processesSteps.length) {
      this.processesSteps.splice(i, 1);
    }
  }

  deleteStageStep(stageIndex: number, stepIndex: number): void {
    const stage = this.stageprocesses[stageIndex];
    if (!stage || !stage.steps) return;
    this.undoStack.push(JSON.parse(JSON.stringify(this.stageprocesses)));
    this.redoStack = [];
    if (stepIndex >= 0 && stepIndex < stage.steps.length) {
      stage.steps.splice(stepIndex, 1);
      if (stage.steps.length === 0) {
        this.stageprocesses.splice(stageIndex, 1);
      }
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
