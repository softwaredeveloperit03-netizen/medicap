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
  selectedStageData=[]
  addStages(value,index){

    if(value == 'ADD NEW'){
      this.addStage = true;
    }
    else{
      this.selectedStageData=this.steps_data[index-1];

    }

  }

  SaveStage(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    // temp['stage']=this.selectedStageData['stage']
    // temp['stage_for']=this.selectedStageData['stage_for']
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
    this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
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
    temp['forms']=this.forms_list[0];
    temp['step']=this.processes;
    this.stageprocesses[this.stageprocesses.length] = temp;
    this.forms_list=[];

  console.log('this.stageprocesses :>> ', this.stageprocesses);
    this.processes=[];
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
    this.service.post('bmr/process.php?type=saveProcessmaster&process_type='+this.process_type+'&dosage_form='+this.dosage_form, JSON.stringify(temp)).subscribe(response => {
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

}
