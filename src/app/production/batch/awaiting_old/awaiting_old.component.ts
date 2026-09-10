import { DatePipe } from '@angular/common';
import { AfterViewInit, Component, OnInit, ViewChild } from '@angular/core';
import { ClarityModule } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute, Router,Params } from '@angular/router';
import { DataService } from '../data.service'
// import QuillBetterTable from 'quill-better-table';
import { QuillEditorComponent } from 'ngx-quill';
import Quill from 'quill';
declare let alertify;
@Component({
  selector: 'app-awaiting_old',
  templateUrl: './awaiting_old.component.html',
  styleUrls: ['./awaiting_old.component.css'],
  providers: [DatePipe]
})
export class Awaiting_oldComponent implements OnInit,AfterViewInit {
  start = '';
  process_values = [
    { "name": "Time of cyclone container change(write N/A if not changed)"},
    { "name": "Time of PCS-7 container change acknowledge(write NA if not chnage)"},
    { "name": "Time for recording the process values"},
    { "name": "Cyclone Fraction ID when values are recored"}
  ];
  process_values2 = [
    { "name": "Chamber inlet temprature","code":"C1K01","target":"142**","unit":"°C","range":"137-147"},
    { "name": "Chamber outlet temprature","code":"E1K01","target":"73","unit":"°C","range":"68-78"},
    { "name": "Drying air flow rate","code":"С1К03","target":"2400","unit":"kg/h","range":"2350 - 2450"},
    { "name": "Cyclone differential pressure","code":"F1P11","target":"95","unit":"mbar","range":"85 - 105"},
    { "name": "Nozzle gas temperature","code":"B1K61","target":"80","unit":"°C","range":"75 - 85"},
    { "name": "B1T61 - two-fluid nozzle","code":"","target":"","unit":"","range":""},
    { "name": "Atomization air pressure","code":"B1P62","target":"13.5*","unit":"bar","range":"12.0 - 15.0"},
    { "name": "Atomization air flow rate","code":"B1K65","target":"215","unit":"kg/h","range":"210 - 220"},
    { "name": "Atomization feed flow","code":"B1P05","target":"25","unit":"kg/h","range":"20 - 30"},
    { "name": "Atomization feed temperature","code":"B1K21","target":"75","unit":"°C","range":"65- 80"},
    { "name": "B1T62 - two-fluid nozzle","code":"","target":"","unit":"","range":""},
    { "name": "Atomization air pressure","code":"B1P63","target":"13.5*","unit":"bar","range":"12.0 - 15.0"},
    { "name": "Atomization air flow rate","code":"B1K66","target":"215","unit":"kg/h","range":"210 - 220"},
    { "name": "Atomization feed flow","code":"B1P06","target":"25","unit":"kg/h","range":"20 - 30"},
    { "name": "Atomization feed temperature","code":"B1K22","target":"75","unit":"°C","range":"65 - 80"},
    { "name": "BIT63 - two-fluid nozzle","code":"","target":"","unit":"","range":""},
    { "name": "Atomization air pressure","code":"B1P64","target":"13.5*","unit":"bar","range":"12.0 - 15.0"},
    { "name": "Atomization air flow rate","code":"B1K67","target":"215","unit":"kg/h","range":"210 - 220"},
    { "name": "Atomization feed flow","code":"B1P07","target":"25","unit":"kg/h","range":"20 - 30"},
    { "name": "Atomization feed temperature","code":"B1K23","target":"75","unit":"°C","range":"65 - 80"},
    // { "name": "","code":"","target":"","unit":"","range":""},
    
   
  ];

  effective_date='';

  IS_deviation=false;
  IS_NcPR=false;
  IS_ebm=false;

  init = ({
    height: 300,
    menubar: false,
    statusbar: false,
    content_style: 'body { font-size: 12pt; font-family: Times; } ol{counter-reset: item}ol > li{display: block}ol > li:before {content: counters(item, ".") ". ";counter-increment: item}',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor table'
    ],
    toolbar:
      // 'table'
      '',
  
      
      setup: (editor) => {
      
        editor.on('init', () => {
          setTableWidth(editor);
        });
      
       
        editor.on('ExecCommand', (event) => {
          const command = event.command;
      
          if (command === 'mceInsertTable') {
            setTableWidth(editor);
          }
        });
      
        function setTableWidth(editor) {
         
          const tables = editor.dom.select('table');
          tables.forEach(table => {
            editor.dom.setStyle(table, 'width', '100%');
          });
        }
      }
      
  });
  
  // api = 'pyc83d69ldr5hmcctu5rdnz66b498vz3nf5wofapfe87o13a';
  api = 'lzydfz1lgit47bg7tnab31cq8uxwfdnetk1vo3lqxe70so59';







  results;
  isView = false;
  isDIGI = false;
  ISdisp_date = false;
  isStart = false;
  selectedResult = [];
  isDate = false;
  id;
  expected_start_date;
  expected_complete_date;
  selectedIndex = -1;
  supervisors;
  newSupervisor = false;
  newWorker= false;
  names;
  operators;
  operator_names;
  isminor=false;
  options2 = [
    { "name": "Procedure","option": "Procedure", "status": false, "list": []},
    { "name": "Instruction","option": "Instruction", "status": false, "list": []}
  ];
  options = [
    { "name": "QA","option": "qa", "status": false, "list": []},
    { "name": "Production","option": "production", "status": false, "list": []}
  ];
  check_list = [
    { "check": "Correct area is in use (as specified in the process instruction)."},
    { "check": "The outer door of the room is status labelled."},
    { "check": "All The Documention, products and materials from the previous production operation have been removed from the room."},
    { "check": "Equipment is appropriate to use - cleaning completed as required."},
    { "check": "Euipment is clean & dry (visible inspection only)."},
    { "check": "Sanitisation is within the time frame as specified in the PI."},
    { "check": "Cleaning tags are attached to the PI and ensure that the major clean has been approved(if applicable)."},
    { "check": "All the steps upto the room  clearance section are completed in the corresonding PI."},
    { "check": "All the logbooks involed are checked for completness(eg.,login,batch details, minor clean/daily clean performed)."},
    { "check": "the balance involved are calibrated and the daily checks performed."},
    { "check": "No alarms on EMS System(ensure akarm and stobe lighting not initiated)."},
    { "check": "only the bill of material listed in the PI are present in the room and are within expiry date."},
    { "check": "Ensure that the details recorded in PI for the bill of materials are accurate."}
   ];
   today='';
   constructor(private service: DataAccessService, private datePipe: DatePipe, private route:ActivatedRoute, private router: Router,private dataService: DataService) { 
   // this.expected_start_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    // this.expected_complete_date = this.datePipe.transform(Date.now(),'yyyy-MM-01'); 
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd HH:mm:ss');

   }


totalNet: number = 0;
tare_wt;
net_wt;
gross_wt;

calcGross() {
    // Ensure both tare_wt and net_wt are valid numbers
    if (!isNaN(parseFloat(this.tare_wt)) && !isNaN(parseFloat(this.net_wt))) {
        // Calculate gross weight
        this.gross_wt = (parseFloat(this.tare_wt) + parseFloat(this.net_wt)).toFixed(2);
    } else {
        // Handle if either tare_wt or net_wt is not a valid number
        this.gross_wt = ''; // or any other appropriate handling
    }
}

add_weigh(data,ids){
  this.totalNet=0;
 let temp=data.value;
this.weighings.push(temp);

for(let i=0;i <this.weighings.length;i++){
  this.totalNet += Number(this.weighings[i]['net_wt']);
}


console.log(ids); 
console.log(this.weighings);
let temp1={};
temp1['weighings']=this.weighings;

this.service.post('production/stages.php?type=addWeighing2&id='+ids, JSON.stringify(temp1)).subscribe(response => {
  if (response['status'] == 'success') {
    this.getReadyBatchPlans();
    alertify.success('Record Save Successfully');
  } else {
    alertify.error(response['status']);
  }
});

}
deleteweighings(index,ids){
  this.totalNet=0;
 
this.weighings.splice(index,1);

for(let i=0;i <this.weighings.length;i++){
  this.totalNet += Number(this.weighings[i]['net_wt']);
}


console.log(ids); 
console.log(this.weighings);
let temp1={};
temp1['weighings']=this.weighings;

this.service.post('production/stages.php?type=addWeighing2&id='+ids, JSON.stringify(temp1)).subscribe(response => {
  if (response['status'] == 'success') {
    this.getReadyBatchPlans();
    alertify.success('Record Save Successfully');
  } else {
    alertify.error(response['status']);
  }
});

}

// updateTotalNet() {
//     // Recalculate total net weight
//     this.totalNet = this.weighings.reduce((acc, add) => acc + parseFloat(add.net_wt || 0), 0);
// }






  ngOnInit() {
    this.getReadyBatchPlans();
    this.getEmployees();
    this.getEmployee();
    this.getRoomChecklist();
    this.getEquipmentsLog();
    this.getLineChecklist();
    this.getEquipments
    this.getUnits();
  }
  units;
  yeild_Total_contgainer=0;
  tranfer_store_qty=0;
  calc_cont(value){
    this.yeild_Total_contgainer=this.tranfer_store_qty/value;
  }
  getUnits() {
    this.units = []
    this.service.get('common.php?type=getUnits_List').subscribe(response => {
      this.units = response
    })
  }
  equipments1;
  getEquipments(){
    this.service.get('engineering/maintenance.php?type=getEquipmentForBreakdown').subscribe(response => {
      this.equipments1 = response;
    });
  }
  selectedEquip=[];
  selectedEquipment(index){
    this.selectedEquip = this.equipments1[index];
  }
  // IS_ebm=false;
  getEmployee(){
    this.service.get('engineering/maintenance.php?type=getEmployee').subscribe(response => {
      this.employees = response;
    });
  }
 
  equipments;
  getEquipmentsLog() {
    this.service.get('master/equipment.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
      console.log(this.results);
      
    });
  } 

  getReadyBatchPlans() {
    this.service.get('production/product.php?type=getReadyBatchPlans&material_type=Raw Material').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.view(this.selectedIndex);
      }
    });
  }

  getEmployees() {
    this.service.get('employee.php?type=getProductionExecutiveOfficers').subscribe(response => {
      this.supervisors = response;
    });
  }

  isallocate=false;
  allocation=false;
  allow(){
    this.allocation=false
  }
  allocate(index){
   
    this.isallocate = true;
    // this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.getCheklists();
    this.geStages();
    this.getInstrunctions();
    this.getAbbrivation();
   this.getbmrEquipments();
   this.getProcedure();
   this.unitformulaRM();
   this.getDepartmentsEmployee();
    // this.isView = true;
  }
  view(index) {
    this.eq_lists = [];
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    // this.isView = true;
    this.isStart = true;
    this.getCheklists();
    this.geStages(); 
    this.getInstrunctions();
    this.getAbbrivation();
   this.getbmrEquipments();
   this.getProcedure();
   this.unitformulaRM();
   this.get_Save_batch_formula_fro_bmr();
   this.get_inprocess_yeild();
   this.getUnits();
   this.getSpec_date(this.selectedResult['product_code']);
  }
  spec_data;
  spc_no;
Analysis_Sample_Qty=0;
Retention_Sample_Qty=0;
total_qty=0;
calc_tot_qty(){
  this.total_qty=Number(this.Analysis_Sample_Qty)+Number(this.Retention_Sample_Qty)
  console.log(this.total_qty)
}
  getSpec_date(data){
    this.service.get('production/stages.php?type=getSpec_date&material_code='+data).subscribe(response=>{
      this.spec_data = response;
      // this.spc_no=this.spec_data[0]['specification_no']
      if (this.spec_data && this.spec_data.length > 0) {
        this.spc_no = this.spec_data[0]['specification_no'];
        // this.spc_tests = this.spec_data[0]['spec_tests'];
    } else {
        console.error('spec_data is undefined or empty');
        // Handle the error, for example, by setting a default value or showing a message
        this.spc_no = 0;
    }
    });
  }
 

  review(index) {
    this.selectedResult = this.results[index];
    this.dataService.setData(this.selectedResult);
    console.log('gdfuidfuidfuidfuidfuidfuidfuidfuidfuidfuidfuidfuidfuidfuidfuidfui')
    this.router.navigate(['/prod-f-ebmr/batch/awaitingcheck']);
  }



  yields:any=[];


  unit_formula_batch_weight;
  batch_formula_weight;
  batch_formulas;
  get_Save_batch_formula_fro_bmr(){
    this.service.get('planning/raw.php?type=get_Save_batch_formula_fro_bmr&product_code='+this.selectedResult['product_code']+'&batch_size='+this.selectedResult['batch_size'])
    .subscribe(response =>{
      this.batch_formulas=response;
      this.unit_formula_batch_weight=this.batch_formulas[0]['unit_formula_batch_weight']
      this.batch_formula_weight=this.batch_formulas[0]['batch_formula_weight']
    });
  }
  yeild;
  get_inprocess_yeild(){
    this.service.get('production/product.php?type=get_inprocess_yeild&from=production&product_code='+this.selectedResult['product_code']+'&work_order_id='+this.selectedResult['work_order_no'])
    .subscribe(response =>{
      this.yields=response;
      this.yeild=this.yields[0]['tranfer_store_qty']
      
    });
  }


  send_data_test(data){
    let temp=data;
    temp['produt_code']=this.selectedResult['product_code'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    console.log(temp);  
    this.service.post('production/stages.php?type=send_to_qc_sampling', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
     data.resetForm();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  sample_withdrwal_qty;
  send_data_test1(data){
    let temp1=data.value
    let temp={};
    temp['produt_code']=this.selectedResult['product_code'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['sample_withdrwal_qty']=this.sample_withdrwal_qty;
    temp['testing_type']='product';
    temp['spc_no']=this.spc_no;
    temp['samp_data']=temp1;
    console.log(temp);  
    this.service.post('production/stages.php?type=send_to_qc_sampling2', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
     data.resetForm();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }




  
  rmmat;
  raw_materials;
packing_configuration;
  unitformulaRM(){
    this.service.get('production/unitformula.php?type=getUnitFormulaLog&product_code='+this.selectedResult['product_code'])
    .subscribe(response =>{
      this.rmmat=response;
      this.raw_materials=this.rmmat[0]['raw_materials'];
      this.packing_configuration=this.rmmat[0]['packing_configuration'];
    });
  }
  instructions;
  getInstrunctions(){
    this.service.get('production/ebmr.php?type=get_Instruction&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.instructions=response;
    });
  }
  abbreviation;
  getAbbrivation(){
    this.service.get('production/ebmr.php?type=getAbbrivation&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.abbreviation=response;
    });
  }
  eqDatas;
  getbmrEquipments(){
    this.service.get('production/ebmr.php?type=getbmrEquipments&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.eqDatas=response;
    });
  }
  Proceduresss;
  getProcedure(){
    this.service.get('production/ebmr.php?type=get_Procedure&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.Proceduresss=response;
    });
  } 
  cheklists;
  getCheklists() {
    this.service.get('master/bmr_checklist.php?type=getCheklistsBMR&product_code='+this.selectedResult['product_code']).subscribe(response => {
      this.cheklists = response;
    });
  }
  Stagess;
  DocumentNo;
  Stages_allocate;
  employees;
  enable=0;
  Stages_es=[]
  Forms1=[];
  Forms=[];
  geStages(){
    this.enable=0;
    this.service.get('bmr/process.php?type=getProcessesBMR&product_code='+this.selectedResult['product_code']+ '&work_order_no='+this.selectedResult['work_order_no']+'&dep_name=Production').subscribe(response => {
      this.Stagess = response;
      this.DocumentNo = this.Stagess[0]['DocumentNo'];
      this.Stages_allocate = this.Stagess[0]['Stages'];
      this.Stages_es = this.Stagess[0]['Stages'];

      console.log('this.stages_es :>> ', this.Stages_es);
      this.Forms1 = this.Stagess[0]['Forms1'];
      this.Forms = this.Stagess[0]['Forms'];
      let countNotNull = 0;

      for (let i = 0; i < this.Stages_es.length; i++) {
          if (this.Stages_es[i].bmr_saved_by !== null) {
              countNotNull++;
          }
      }
      console.log("countNotNull",countNotNull);
     

        this.enable=countNotNull;
      
       
      
      
      
      console.log("Count of Stages_es where bmr_saved_by is not null:", this.enable);
      





      console.log(this.Stages_es);
    });

  }
  getDepartmentsEmployee() {
    this.service.get('hr/attendance.php?type=getDepartmentEmployees&department_name=Production').subscribe(response => {
      this.employees = response;
    });
  }
  selectedStage_Stages;
  stepsss=false;
  selectedStage=[];
  stage_id;
  assigned=0;
  selectStagePage(index, index1) {
    this.isConfigure0=true;
    this.isConfigure1=false;
    this.emp_id = localStorage.getItem('emp_id');

    this.selectedPage = 99;  
      // this.selectedStage = this.Stagess[index];
      this.selectedStage = this.Stages_es[index];
      this.selectedStage_Stages = this.Stages_es[index];
      this.stage_id = this.selectedStage['id'];  
    this.stepsss=true
    this.stepsss_table=false;   
    for (let i = 0; i < this.selectedStage_Stages.length; i++) {
      const stage = this.selectedStage_Stages[i];
      if (stage.Alternate_Officer === this.emp_id ||
          stage.Production_Office === this.emp_id ||
          stage.Supervisor === this.emp_id) {
          // If emp_id is found in any of the specified columns, set assigned to 1
          this.selectedStage_Stages[i].assigned = 1;
      } else {
          // If emp_id is not found in any of the specified columns, set assigned to 0
          this.selectedStage_Stages[i].assigned = 0;
      }
  }
 
  console.log(this.selectedStage_Stages)
  console.log(index)
  
  }


  firstNullStageEncountered: boolean = false;

  isFirstNullStage(stage, index): boolean {
    if (!this.firstNullStageEncountered && stage.bmr_saved_by === null && index === 0) {
      this.firstNullStageEncountered = true;
      return false; // Enable the first null stage
    } else {
      return true; // Disable other stages
    }
  }












  steps2;
instruction;
procedures:any=[];
isroomActions;
roomActions:any=[];
Logbook:any=[];
CleaningChecks:any=[];
equipments_cleaning:any=[];
isCleaningChecks;
isroom;
dispensing;
room:any=[];
Qa_LINE_clearance;
prod_LINE_clearance;
equipmentsss;
LineClearance;
bmr_LineClearance;
bmr_LineClearance_status;
weighings;
initial_checks;
environmentsss;
inprocess;
isprocedure;
isinstruction;
isequipment;
isclerance;
isweighing;
isenvironment;
isinitial;
isinprocess;
seq_list:any=[]
  stages_id;
  bmr_procedure_status;
  bmr_equipment;
  bmr_CleaningChecks;
  bmr_CleaningChecks_status;
  bmr_equipment_status;
  isNewStage2=false;
  stages_step_id;
  bmr_weighing;
  bmr_pocedure;
  bmr_weighing_status;

  bmr_room;
bmr_room_status;
mfg_stage_id;
mfg_step_id;
bmr_roomActions;
bmr_roomActions_status;

QcSample:any=[];
bmr_QcSample;
bmr_QcSample_status;








saveallocarion(index){

  
  let temp={}
  temp['producton_ofc']=this.Stages_allocate[index]['producton_ofc'];
  temp['alt_ofc']=this.Stages_allocate[index]['alt_ofc'];
  temp['supervisor']=this.Stages_allocate[index]['supervisor'];
  temp['remark']=this.Stages_allocate[index]['remark'];
  temp['id']=this.Stages_allocate[index]['id'];

  this.service.post('bmr/bmr.php?type=saveallocarion&id=', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      this.getReadyBatchPlans();
      alertify.success('Record Save Successfully');
    } else {
      alertify.error(response['status']);
    }
  });

}
save_disp_dtl(work_order_id,  material_code,  net_total,  ar_no){

  let temp={}
  temp['work_order_id']=work_order_id
  temp['material_code']=material_code
  temp['net_total']=net_total
  temp['ar_no']=ar_no
  temp['batch_plan_id']=this.selectedResult['batch_plan_id'];
  temp['despensedQty']=net_total
  temp['lot_id']=this.selected_dispIndex1['lot_id'];

  this.service.post('store/dispensing.php?type=saveDispensingFormProduction', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      this.getReadyBatchPlans();
      alertify.success('Record Save Successfully');
    } else {
      alertify.error(response['status']);
    }
  });

}





tables;
bmr_table;
bmr_table_status;
stp_name='';
isprocedure_time_stamp;
isLineClearance_time_stamp;
isCleaningChecks_time_stamp;
isroomActions_time_stamp;
isroom_time_stamp;
  stage_steps(id,id1,stp_name){
    this.totalNet=0;
    // for(let i=0; i< this.Stagess; i++){
    //   this.stages_id=this.Stagess[i]
    // }
this.mfg_stage_id=id
this.mfg_step_id=id1
this.stp_name=stp_name
console.log('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa='+this.stp_name)

    let temp={};
    temp['stage_id']=id1;
    temp['step_id']=id;
    this.isNewStage2 = true;
    this.isNewStage2 = false;
    this.isNewStage2 = true;


    console.log(temp);
  
  
    this.service.get('production/stages.php?type=GET_bmr_stages_steps&stpe_id='+id+'&Stage_id='+id1+'&work_order_no='+this.selectedResult['work_order_no']).subscribe(response =>{
      this.steps2 =response;



 if (Array.isArray(this.steps2) && this.steps2.length > 0) {
      // this.stages_step_id = this.steps2[0]['id'];
      
      this.stages_step_id=this.steps2[0]['id'];
      this.procedures=this.steps2[0]['procedures'];
      this.bmr_pocedure=this.steps2[0]['bmr_pocedure'];
      this.bmr_procedure_status=this.steps2[0]['bmr_procedure_status'];

      this.equipmentsss=this.steps2[0]['equipment'];
      this.bmr_equipment=this.steps2[0]['bmr_equipment'];
      this.bmr_equipment_status=this.steps2[0]['bmr_equipment_status'];
      
      this.LineClearance=this.steps2[0]['LineClearance'];
      this.bmr_LineClearance=this.steps2[0]['bmr_LineClearance'];
      this.bmr_LineClearance_status=this.steps2[0]['bmr_LineClearance_status'];

      this.CleaningChecks=this.steps2[0]['CleaningChecks'];
      this.bmr_CleaningChecks=this.steps2[0]['bmr_CleaningChecks'];
      this.bmr_CleaningChecks_status=this.steps2[0]['bmr_CleaningChecks_status'];

      this.room=this.steps2[0]['room'];
      this.bmr_room=this.steps2[0]['bmr_room'];
      this.bmr_room_status=this.steps2[0]['bmr_room_status'];


      this.QcSample=this.steps2[0]['qcSamples'];
      this.bmr_QcSample=this.steps2[0]['bmr_QcSample'];
      this.bmr_QcSample_status=this.steps2[0]['bmr_QcSample_status'];
      
      this.instruction=this.steps2[0]['instructions'];
     //  this.Qa_LINE_clearance=this.steps2[0]['Qa_LINE_clearance'];
     //  this.prod_LINE_clearance=this.steps2[0]['prod_LINE_clearance'];
      this.weighings=this.steps2[0]['weighing'];
      this.bmr_weighing=this.steps2[0]['bmr_weighing'];
      this.bmr_weighing_status=this.steps2[0]['bmr_weighing_status'];

      this.tables=this.steps2[0]['tables'];
      console.log('this.tables',this.tables);
      this.bmr_table=this.steps2[0]['bmr_table'];
      this.bmr_table_status=this.steps2[0]['bmr_table_status'];

      this.roomActions=this.steps2[0]['roomActions'];
      this.bmr_roomActions=this.steps2[0]['bmr_roomActions'];
      this.bmr_roomActions_status=this.steps2[0]['bmr_roomActions_status'];

      this.initial_checks=this.steps2[0]['initial_checks'];
      this.environmentsss=this.steps2[0]['environments'];
      this.inprocess=this.steps2[0]['inprocess'];
      this.Logbook=this.steps2[0]['Logbook'];
      this.equipments_cleaning=this.steps2[0]['equipments_cleaning'];
      console.log('equipments_cleaning='+this.equipments_cleaning);
      
      // for dd
      this.isprocedure=this.steps2[0]['isprocedure']
      this.isinstruction=this.steps2[0]['isinstruction']
      this.isequipment=this.steps2[0]['isequipment']
      this.isclerance=this.steps2[0]['isclerance']
      this.isweighing=this.steps2[0]['isweighing']
      this.isenvironment=this.steps2[0]['isenvironment']
      this.isinitial=this.steps2[0]['isinitial']
      this.isinprocess=this.steps2[0]['isinprocess']
      this.isroom=this.steps2[0]['isroom'];
      this.isroomActions=this.steps2[0]['isroomActions'];
      this.isCleaningChecks=this.steps2[0]['isCleaningChecks'];
      
      
      this.isprocedure_time_stamp=this.steps2[0]['isprocedure_time_stamp'];
      this.isLineClearance_time_stamp=this.steps2[0]['isLineClearance_time_stamp'];
      this.isCleaningChecks_time_stamp=this.steps2[0]['isCleaningChecks_time_stamp'];
      this.isroomActions_time_stamp=this.steps2[0]['isroomActions_time_stamp'];
      this.isroom_time_stamp=this.steps2[0]['isroom_time_stamp'];



      this.Dispensing_start_time=this.steps2[0]['bmr_Dispensing_start_time'];
      this.Dispensing_stop_time=this.steps2[0]['bmr_Dispensing_stop_time'];
      this.Proceduresstart=this.steps2[0]['bmr_Proceduresstart'];
      this.Proceduresstop=this.steps2[0]['bmr_Proceduresstop'];
      this.roomActionsstart=this.steps2[0]['bmr_roomActionsstart'];
      this.roomActionsstop=this.steps2[0]['bmr_roomActionsstop'];
      this.roomstart=this.steps2[0]['bmr_roomstart'];
      this.roomstop=this.steps2[0]['bmr_roomstop'];
      this.CleaningChecksstart=this.steps2[0]['bmr_CleaningChecksstart'];
      this.CleaningChecksstop=this.steps2[0]['bmr_CleaningChecksstop'];
      
      
      this.dispensing=this.steps2[0]['dispensing'];
      
      //  sequence
      this.seq_list=JSON.parse(this.steps2[0]['sequence']);
    } else {
      console.warn('Received empty or invalid steps2:', this.steps2);
      this.stages_step_id = null; // or handle appropriately
    }
  




      //  console.log(this.sequence)

      this.bmr_stages(id,id1);
      this.get_bmr_disp_dtl();



      for(let i=0;i <this.weighings.length;i++){
        this.totalNet += Number(this.weighings[i]['net_wt']);
      }

    });
    
  }
  bmr_stages(id,id1){
    // for(let i=0; i< this.Stagess; i++){
    //   this.stages_id=this.Stagess[i]
    // }
     
    let temp={};
    temp['stage_id']=id1;
    temp['step_id']=id;
    temp['product_code']=this.selectedResult['product_code'];
    temp['work_order_no']=this.selectedResult['work_order_no'];
    temp['stages_step_id']=this.stages_step_id;
    temp['bmr_logbook']=this.equipments_cleaning;



    console.log(temp);
  
  
    this.service.post('bmr/bmr.php?type=bmr_stages&stpe_id='+id+'&Stage_id='+id1, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {

        // alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  updateBMRwo(status){
    // for(let i=0; i< this.Stagess; i++){
    //   this.stages_id=this.Stagess[i]
    // }
     
    let temp={};
   temp['wo_number']=this.selectedResult['work_order_no'];
   temp['status']=status;
   temp['actual_yeild']=this.yeild;
     

    console.log(temp);
  
  
    this.service.post('production/stages.php?type=updateBMRwo', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {

        // alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  send_cehck(id){
    // for(let i=0; i< this.Stagess; i++){
    //   this.stages_id=this.Stagess[i]
    // }
    let temp={};
   
    temp['step_id']=id;

console.log('id :>> ', id);

    console.log(id);
  
  
    this.service.post('production/stages.php?type=send_cehck&stage_id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        this.newSupervisor = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  tablesS;
  tableDatas;
  stepsss_table=false;


  method;
  methods: any = {};

  view_table(id){
  
    let temp={};

    temp['step_id']=id;
    


    console.log(temp);
  
  
    this.service.get('bmr/process.php?type=getSteps_table&stpe_id='+id).subscribe(response =>{
      this.method = response;
      this.methods=this.method[0];
       
      //  console.log(this.sequence)
    });
    this.stepsss_table = true;
    this.stepsss =false;
    this.isRoomchecks=false;
    this.isminor=false;
    this.isequip=false
    this.isAttend=false 
    
  }
  isConfigure0=false;
  isConfigure1=false;
  selectedStage_Stagess;
  selectedStage_Stagess_step;
  stge_name='';
  get_stages(id,stge){
    this.selectedPage = 99;  
  

    this.isConfigure0=false;
    this.isConfigure1=true;
    this.stge_name=stge
  
  
    this.service.get('bmr/process.php?type=get_stages&stage_id='+id).subscribe(response =>{
      this.selectedStage_Stagess = response;
      this.selectedStage_Stagess_step = this.selectedStage_Stagess[0]['steps'];
    });
    console.log('selectedStage_Stagess='+this.selectedStage_Stagess);
    console.log(this.selectedStage_Stagess_step);
    this.stepsss_table = true;
    this.stepsss =false;
    this.isRoomchecks=false;
    this.isminor=false;
    this.isequip=false
    this.isAttend=false 
    this.isdynamuc=false 
    this.isAttend=false 
    
  }


  isNewSupervisors(id) {
    this.id = id;
    this.newSupervisor = true;
  }

  isNewWorkers(id) {
    this.id = id;
    this.newWorker = true;
  }
  selectedcheklists=[];
  startBatch(id,index) {
    this.isStart = true;
    this.isView = false;   
    this.getaddnewProcess1(id);
    this.selectedcheklists=this.cheklists[index];
    console.log(this.selectedcheklists)


  }
  checkpoints
  getaddnewProcess1(id){
    this.service.get('master/bmr_checklist.php?type=BMRgetaddnewProcess1&bmr_checklisthead_no_id='+id).subscribe(response=>{
      this.checkpoints = response;
    });
  }
  isdynamuc=false;
  selectedPage:any=[];
  isRoomchecks=false;
  isAttend=false
  isequip=false
  isEncapsule=false;
  first_record=false;
  second_record=false;
  third_record=false;
  fourth_record=false;
  yield=false;
  getclean_data=[]
  qc_sampling=false;
  selectPage(index) {
    this.isdynamuc=true;
    this.isRoomchecks=false;
    this.isminor=false;
    this.isequip=false
    this.isAttend=false 
    this.stepsss_table = false;
    this.stepsss =false;
    this.isRoomchecks=false;
    this.isminor=false;
    this.isequip=false
    this.isAttend=false 
    this.isAttend=false 
    this.isAttend=false 
    this.selectedPage = index;
    console.log(this.selectedPage)
    this.stepsss=false;
    if(index==13){
      this.isEncapsule=true;
      console.log('hellllo')
    }
    else if(index==16){
       this.first_record=true;
     console.log('hellllo')
    }
    else if(index==17){
      this.second_record=true;
      console.log('hellllo')
    }
    else if(index==18){
      this.third_record=true;
      console.log('hellllo')
    }
    else if(index==19){
      this.fourth_record=true;
      console.log('hellllo')
    }
    else if(index==20){
      this.yield=true;
      console.log('hellllo')
    }
    else if(index==22){
      this.qc_sampling=true;
      console.log('hellllo')
    }



    this.getCleanEquipTag()
    this.getroomClearanceForm()
    this.getbmr_empty_rm_capsule_bag_weight_record()
    this.getqcSample_form_list()
    this.getfilled_cap_list()
    this.get_bmr_disp_dtl()

  }
    //========= Quill Table ===========
    @ViewChild('editorQuill', { static: false }) editorQuill: QuillEditorComponent;
    editorModules1 = {
      toolbar: [
        ['bold', 'italic', 'underline'], // toggled buttons
        ['blockquote'],
  
        [{ header: 1 }, { header: 2 }], // custom button values
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ script: 'sub' }, { script: 'super' }], // superscript/subscript
        [{ indent: '-1' }, { indent: '+1' }], // outdent/indent
        [{ direction: 'rtl' }], // text direction
  
        [{ size: ['small', false, 'large', 'huge'] }], // custom dropdown
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
  
        [{ color: [] }, { background: [] }], // dropdown with defaults from theme
        [{ 'font':[]}], 
        [{ align: [] }],
        ['clean'], // remove formatting button
        ['image',]
      ],
      table: true, // Register the `better-table` module for table editing
      'better-table': {
        operationMenu: {
          items: {
            unmergeCells: {
              text: 'Another unmerge cells name',
            },
          },
          color: {
            colors: ['#fff', 'rgb(0, 0, 0)', 'red'],
            text: 'Background Colors:',
          },
        },
        clipboard: {
          matchVisual: false,
        },
      },
      keyboard: {
        // bindings: QuillBetterTable.keyboardBindings,
      },
    };
    
    editormodules = this.editorModules1;
    lastTable: HTMLElement | null = null;
    isTable:boolean = false;
    row: number;
    column: number;
    onSubmit(form) {
      if (form.valid) {
        console.log('Rows:', this.row, 'Columns:', this.column);
        this.insert3x3Table();
      }
    }
    insert3x3Table() {
      console.log('this.editorQuill',this.editorQuill);
      if (this.editorQuill) {
        console.log(this.editorQuill);
        const quill = this.editorQuill.quillEditor;
        const tableModule: any = quill.getModule('better-table');
        console.log('tableModule',tableModule);
        if (tableModule && this.row && this.column) {
          tableModule.insertTable(this.row, this.column);
           // Store a reference to the last inserted table
        this.lastTable = quill.root.querySelector('table:last-of-type') as HTMLElement;
        this.isTable = false;
        this.row = null;
        this.column = null;
        } else{
          alert('Please Select the Enter the row and column');
        }
      }
    }
    removeLastTable() {
      if (this.lastTable) {
        this.lastTable.remove();
        this.lastTable = null; // Clear the reference after removal
      }
    }
    ngAfterViewInit(): void {
      // Quill.register('modules/better-table', QuillBetterTable);
    }
  

  isRoomcheck(){
    this.isRoomchecks=true;
    this.isdynamuc=false;
    this.isAttend=false;
    this.isminor=false;
    this.isequip=false
  }
  ismino(){
    this.isRoomchecks=false;
    this.isAttend=false;
    this.isdynamuc=false;
    this.isequip=false
    this.isminor=true;
  }
  isatte(){
    this.isRoomchecks=false;
    this.isdynamuc=false;
    this.isminor=false;
    this.isAttend=true
    this.isequip=false
  }
  isequips(){
    this.isRoomchecks=false;
    this.isdynamuc=false;
    this.isminor=false;
    this.isAttend=false
    this.isequip=true

  }



  sendDate(stage, action, value) {
    this.service.get('production/product.php?type=saveWorkAllocation&action=' + action + '&value=' + value + '&id=' + stage.id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Records Svae Successfully');
        this.getReadyBatchPlans();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  savr_Yeild_Form(data){
    let temp = data.value;
    temp['product_name'] = this.selectedResult['product_name'];
    temp['product_type'] = this.selectedResult['product_type'];
    temp['product_code'] = this.selectedResult['product_code'];
    temp['work_order_no'] = this.selectedResult['work_order_no'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['from'] = 'production';
    this.service.post('production/product.php?type=saveYeild_inoprocess', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.get_inprocess_yeild();
     data.resetForm();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  saveSupervisor(){
    let temp = {};
    temp['supervisors'] = this.names;
    temp['id'] = this.id;
    this.service.post('production/product.php?type=saveSupervisors', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        this.newSupervisor = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }

  saveWorker(){
    let temp = {};
    temp['workers'] = this.operator_names;
    temp['id'] = this.id;
    this.service.post('production/product.php?type=saveWorkers', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        this.newWorker = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }

  selectedEquips=[];
  selectedEquipsss=[];
  eq_lists=[];
  add_equpmentss(index){
    this.selectedEquips=this.equipments[index-1];
  }
  add_equpmentssss(index){
    this.selectedEquipsss=this.eq_lists[index-1];
  }
  add_equipment(){
    let temp=this.selectedEquips;
    this.eq_lists[this.eq_lists.length]=temp;
    console.log(this.eq_lists)
  }
   
  addchcek(data){
    let temp = data.value;
    this.check_list[this.check_list.length]=temp;
    data.resetForm();
  }
  delchcek(index){
    this.check_list.splice(index,1);
  }
  Savechcek(){
   let temp={}
    temp["room_checks"]=this.check_list;
    console.log(temp);
  }
  minor_list=[];
  addminor(data){
    let temp = data.value;
    this.minor_list[this.minor_list.length]=temp;
    data.resetForm();
  }
  delminor(index){
    this.minor_list.splice(index,1);
  }
  Saveminor(){
   let temp={}
    temp["minor_list"]=this.minor_list;
    console.log(temp);
  }
  atten_list=[];
  addatten(data){
    let temp = data.value;
    this.atten_list[this.atten_list.length]=temp;
    data.resetForm();
  }
  delatten(index){
    this.atten_list.splice(index,1);
  }
  Saveatten(){
   let temp={}
    temp["atten_list"]=this.atten_list;
    console.log(temp);
  }
  equip_list=[];
  addequip(data){
    let temp = data.value;
    this.equip_list[this.equip_list.length]=temp;
    data.resetForm();
  }
  delequip(index){
    this.equip_list.splice(index,1);
  }
  equipments_clean=[];
  Saveequip(){
    let temp=this.selectedEquipsss;
    temp["equip_list"]=this.equip_list;
    this.equipments_clean[this.equipments_clean.length]=temp
    console.log(temp);
  }
  SaveSelectedPage(){
  //  let temp={}
  //   temp["checklist"]=this.selectedPage?.checkList;
   let temp=this.selectedPage?.checkList;
    console.log(temp);
  }

 
  saveInstruction(id){
    let temp={}
    temp['instructon']=this.instruction;

    this.service.post('production/stages.php?type=saveInstruction2&id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });

  }
  saveProcedure(id,type,index){
    let temp={}
    if(type=='procedure'){
      temp['procedures']=this.procedures;
      temp['step']=type;
    }
    else if(type=='LineClearance'){
      temp['LineClearance']=this.LineClearance;
      temp['step']=type;
    }
    else if(type=='procedure_check'){
      // temp['procedures']=this.procedures;
      temp['step']=type;
    }
    else if(type=='Equipments'){
      temp['equipmentsss']=this.equipmentsss;
      temp['step']=type;
    }
    else if(type=='Equipments_check'){
      // temp['equipmentsss']=this.equipmentsss;
      temp['step']=type;
    }
    else if(type=='Cleaning_Checks'){
      temp['CleaningChecks']=this.CleaningChecks;
      temp['step']=type;
    }
    else if(type=='Cleaning_Checks_check'){
      // temp['CleaningChecks']=this.CleaningChecks;
      temp['step']=type;
    }
    else if(type=='Room'){
      temp['room']=this.room;
      temp['step']=type;
    }
    else if(type=='Room_check'){
      // temp['room']=this.room;
      temp['step']=type;
    }
    else if(type=='roomActions'){
      temp['roomActions']=this.roomActions;
      temp['step']=type;
    }
    else if(type=='roomActions_check'){
      // temp['roomActions']=this.roomActions;
      temp['step']=type;
    }
    else if(type=='tabless'){
      temp['tables']=this.tables;
      temp['step']=type;
    }
    else if(type=='tabless_check'){
      // temp['weighings']=this.weighings;
      temp['step']=type;
    }
    else if(type=='logbook_prepare_master'){
      temp['PrepareMaster']=this.equipments_cleaning[index]['PrepareMaster'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_prepare_master_check'){
      // temp['PrepareMaster']=this.equipments_cleaning[index]['PrepareMaster'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_RoomLogin'){
      temp['RoomLogin']=this.equipments_cleaning[index]['RoomLogin'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_RoomLogin_check'){
      // temp['RoomLogin']=this.equipments_cleaning[index]['RoomLogin'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_RoomLogbook'){
      temp['RoomLogbook']=this.equipments_cleaning[index]['RoomLogbook'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_RoomLogbook_check'){
      // temp['RoomLogbook']=this.equipments_cleaning[index]['RoomLogbook'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_RoomLogout'){
      temp['RoomLogout']=this.equipments_cleaning[index]['RoomLogout'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_RoomLogout_check'){
      // temp['RoomLogout']=this.equipments_cleaning[index]['RoomLogout'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_EquipmentCleaning'){
      temp['EquipmentCleaning']=this.equipments_cleaning[index]['EquipmentCleaning'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_EquipmentCleaning_check'){
      // temp['EquipmentCleaning']=this.equipments_cleaning[index]['EquipmentCleaning'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_majorClean'){
      temp['majorClean']=this.equipments_cleaning[index]['majorClean'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }
    else if(type=='logbook_majorClean_check'){
      // temp['majorClean']=this.equipments_cleaning[index]['majorClean'];
      temp['id']=this.equipments_cleaning[index]['id'];
      temp['step']=type;
    }

    this.service.post('bmr/bmr.php?type=saveProcedure2&id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        this.stage_steps(this.mfg_stage_id,this.mfg_step_id,this.stp_name)
        // alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });

  };
  rem=0;
  check_action(value: string) {
    if (value.match(/\bNo\b/)) {
      this.rem = 1;  // Use = for assignment
      console.log('value :>> ', value);
      console.log('this.rem :>> ', this.rem);
    } else {
      this.rem = 0;  // Use = for assignment
    }
  }
  is_ebm2=false;
  IS_deviation111=false;
  IS_deviation1=false;
  reIS_deviation11=0;
  check_action1(value: string) {
    if (value.match(/\bYes\b/)) {
      this.isNewStage2 =false;  // Use = for assignment
      this.IS_deviation1 =true;  // Use = for assignment
      this.reIS_deviation11 = 0;  // Use = for assignment
   
      console.log('value :>> ', value);
      console.log('this.rem :>> ', this.rem);
    } else {
      this.reIS_deviation11 = 1;  // Use = for assignment
    }
  }
  Proceduresstart='';
  Proceduresstop='';
  LineClearancestart='';
  LineClearancestop='';
  roomActionsstart='';
  roomActionsstop='';
  roomstart='';
  roomstop='';
  CleaningChecksstart='';
  CleaningChecksstop='';
  Dispensing_start_time='';
  startTime(value,ids) {
    if(value=='Dispensing_start_time'){
      this.Dispensing_start_time = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['Dispensing_start_time']=this.Dispensing_start_time;
      temp['type']='start';
      this.service.post('production/stages.php?type=addDispencestart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {

          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });

    console.log(this.Proceduresstart);
    }
    if(value=='Procedures'){
      this.Proceduresstart = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['Proceduresstart']=this.Proceduresstart;
      temp['type']='start';
      this.service.post('production/stages.php?type=addProceduresstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {

          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });

    console.log(this.Proceduresstart);
    }
    if(value=='LineClearance'){
      this.LineClearancestart = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['LineClearancestart']=this.LineClearancestart;
      temp['type']='start';
      this.service.post('production/stages.php?type=addLineClearancestart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {

          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
    console.log(this.LineClearancestart);
    }
    if(value=='roomActions'){
      this.roomActionsstart = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['roomActionsstart']=this.roomActionsstart;
      temp['type']='start';
      this.service.post('production/stages.php?type=addroomActionsstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {

          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
    console.log(this.roomActionsstart);
    }
    if(value=='room'){
      this.roomstart = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['roomstart']=this.roomstart;
      temp['type']='start';
      this.service.post('production/stages.php?type=addroomstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {

          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
    console.log(this.roomstart);
    }
    if(value=='CleaningChecks'){
      this.CleaningChecksstart = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['CleaningChecksstart']=this.CleaningChecksstart;
      temp['type']='start';
      this.service.post('production/stages.php?type=addCleaningChecksstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {

          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
    console.log(this.CleaningChecksstart);
    }
  }
  Dispensing_stop_time='';
  stopTime(value,ids) {
    if(value=='Dispensing_stop_time'){
      this.Dispensing_stop_time = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['Dispensing_stop_time']=this.Dispensing_stop_time;
      temp['type']='stop';
      this.service.post('production/stages.php?type=addDispencestart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
      console.log(this.Proceduresstop);
    
    }
    if(value=='Procedures'){
      this.Proceduresstop = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['Proceduresstop']=this.Proceduresstop;
      temp['type']='stop';
      this.service.post('production/stages.php?type=addProceduresstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
      console.log(this.Proceduresstop);
    
    }
    if(value=='LineClearance'){
      this.LineClearancestop = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['LineClearancestop']=this.LineClearancestop;
      temp['type']='stop';
      this.service.post('production/stages.php?type=addLineClearancestart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
      console.log(this.LineClearancestop);
    
    }
    if(value=='roomActions'){
      this.roomActionsstop = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['roomActionsstop']=this.roomActionsstop;
      temp['type']='stop';
      this.service.post('production/stages.php?type=addroomActionsstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
      console.log(this.roomActionsstop);
    
    }
    if(value=='room'){
      this.roomstop = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['roomstop']=this.roomstop;
      temp['type']='stop';
      this.service.post('production/stages.php?type=addroomstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
      console.log(this.roomstop);
    
    }
    if(value=='CleaningChecks'){
      this.CleaningChecksstop = this.datePipe.transform(Date.now(), 'dd-mm-yyyy HH:mm:ss');
      let temp={}
      temp['CleaningChecksstop']=this.CleaningChecksstop;
      temp['type']='stop';
      this.service.post('production/stages.php?type=addCleaningChecksstart_stop&id='+ids, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success('Record Save Successfully');
        } else {
          alertify.error(response['status']);
        }
      });
      console.log(this.CleaningChecksstop);
    
    }
  }





  saveequipment(id){
    let temp={}
    temp['equipments']=this.equipmentsss;

    this.service.post('production/stages.php?type=saveequipment2&id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });

  }
  saveWeighing(id){
    let temp={}
    temp['weighings']=this.weighings;

    this.service.post('production/stages.php?type=saveWeighing2&id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });

  }
  saveInitial(id){
    let temp={}
    temp['initial_checks']=this.initial_checks;


    this.service.post('production/stages.php?type=saveinitial_checks2&id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });

  }
  saveinprocess(id){
    let temp={}
    temp['inprocess']=this.inprocess;

    this.service.post('production/stages.php?type=saveinprocess2&id='+id, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getReadyBatchPlans();
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });

  }

  selectedEquipment_data=[];
  getEquipmentDetails(index){
    this.selectedEquipment_data=this.equipments[index-1]
   
  }
  selectedEquipment_data2=[];
  getEquipmentDetails2(index){
    this.selectedEquipment_data2=this.eqDatas[index-1]
    console.log(this.selectedEquipment_data2);
   
  }
  CleaStatusList=[];
  addCleaStatus(data){
    let temp=data.value;
   this.CleaStatusList[this.CleaStatusList.length]=temp
   data.resetForm();
    console.log(this.CleaStatusList);
  }
  RoomClearanceCheckList;
  getRoomChecklist() {
    this.service.get('production/stages.php?type=getRoomChecklist')
    .subscribe(response => {
      this.RoomClearanceCheckList = response;
    });
 
  }
  delCleaStatusList(index) {
    this.CleaStatusList.splice(index, 1);
  }

  LineClearanceCheckList;
  getLineChecklist() {
    this.service.get('production/stages.php?type=getLineChecklist')
    .subscribe(response => {
      this.LineClearanceCheckList = response;
    });
 
  }



















  idd;
  typeee;
  indexxxxx;
  emp_id = '';
  openDigiSign(id,type,index){
    this.emp_id = localStorage.getItem('emp_id');
    console.log(this.emp_id)
    this.idd = id;
    this.typeee = type;
    this.indexxxxx = index;
    this.isDIGI = true;
  }

  loginPassward ='';
  isbutton = true;
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
         this.saveProcedure(this.idd,this.typeee,this.indexxxxx);
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }





  saveCleanEquipTag(data){
    // if (!data.valid) {
    //   alert('Passward OR Login PIN Required!!!!');
    //   return;
    // }
    if (this.CleaStatusList.length==0) {
      alert('Add Cleaning Status!!!!');
      return;
    }
    let temp = data.value;
    temp['equipment_code']=this.selectedEquipment_data['equipment_code'];
    temp['CleaStatusList']=this.CleaStatusList;
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['work_order_no']=this.selectedResult['work_order_no'];
    temp['DocumentNo']=this.DocumentNo

    this.service.post('bmr/bmr.php?type=saveCleanEquipTag', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.CleaStatusList=[];
        this.newWorker = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  
  CleanEquipTags
  len:number;
  getCleanEquipTag() {
    let temp={};
    temp["product_code"]=this.selectedResult['product_code'],
    temp["batch_number"]=this.selectedResult['batch_number'],
    temp["work_order_no"]=this.selectedResult['work_order_no'],
    temp["DocumentNo"]=this.DocumentNo
    this.service.post('bmr/bmr.php?type=getCleanEquipTag',JSON.stringify(temp)).subscribe(response => {
      this.CleanEquipTags = response;
     this.len = this.CleanEquipTags.length;  
    //  console.log('len :>> ', len);
    });
  }

  
  saveroomClearanceForm(data){
    // if (!data.valid) {
    //   alert('Passward OR Login PIN Required!!!!');
    //   return;
    // }
  
    let temp = data.value;
    temp['equipment_code']=this.selectedEquipment_data['equipment_code'];
    temp['CleaStatusList']=this.RoomClearanceCheckList;
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['work_order_no']=this.selectedResult['work_order_no'];
    temp['DocumentNo']=this.DocumentNo

    this.service.post('bmr/bmr.php?type=saveroomClearanceForm', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
     this.getroomClearanceForm()
        this.newWorker = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  roomClearanceF;
  getroomClearanceForm() {
    let temp={};
    temp["product_code"]=this.selectedResult['product_code'],
    temp["batch_number"]=this.selectedResult['batch_number'],
    temp["work_order_no"]=this.selectedResult['work_order_no'],
    temp["DocumentNo"]=this.DocumentNo
    this.service.post('bmr/bmr.php?type=getroomClearanceForm',JSON.stringify(temp)).subscribe(response => {
      this.roomClearanceF = response;
    });
  }
  save_bmr_empty_rm_capsule_bag_weight_record(data){
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
  
    let temp = data.value;
   
    temp['empy_rm_wt_list']=this.empy_rm_wt_list;
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['work_order_no']=this.selectedResult['work_order_no'];
    temp['DocumentNo']=this.DocumentNo

    this.service.post('bmr/bmr.php?type=bmr_empty_rm_capsule_bag_weight_record', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
     this.getbmr_empty_rm_capsule_bag_weight_record()
        this.newWorker = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  empty_rm_capsule;
  getbmr_empty_rm_capsule_bag_weight_record() {
    let temp={};
    temp["product_code"]=this.selectedResult['product_code'],
    temp["batch_number"]=this.selectedResult['batch_number'],
    temp["work_order_no"]=this.selectedResult['work_order_no'],
    temp["DocumentNo"]=this.DocumentNo
    this.service.post('bmr/bmr.php?type=getbmr_empty_rm_capsule_bag_weight_record',JSON.stringify(temp)).subscribe(response => {
      this.empty_rm_capsule = response;
    });
  }
  save_qcSample_form(data){
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
  
    let temp = data.value;
   
    temp['qcSample_form_list']=this.qcSample_form_list;
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['work_order_no']=this.selectedResult['work_order_no'];
    temp['DocumentNo']=this.DocumentNo

    this.service.post('bmr/bmr.php?type=save_qcSample_form', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
     this.getqcSample_form_list()
        this.newWorker = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  qc_sample_forms;
  getqcSample_form_list() {
    let temp={};
    temp["product_code"]=this.selectedResult['product_code'],
    temp["batch_number"]=this.selectedResult['batch_number'],
    temp["work_order_no"]=this.selectedResult['work_order_no'],
    temp["DocumentNo"]=this.DocumentNo
    this.service.post('bmr/bmr.php?type=getqcSample_form_list',JSON.stringify(temp)).subscribe(response => {
      this.qc_sample_forms = response;
    });
  }
  savefilled_cap_list(data){
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
  
    let temp = data.value;
   
    temp['filled_cap_list']=this.filled_cap_list;
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_number']=this.selectedResult['batch_number'];
    temp['work_order_no']=this.selectedResult['work_order_no'];
    temp['DocumentNo']=this.DocumentNo

    this.service.post('bmr/bmr.php?type=savefilled_cap_list', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
     this.getfilled_cap_list()
        this.newWorker = false;
        alertify.success('Record Save Successfully');
      } else {
        alertify.error(response['status']);
      }
    });
  }
  filled_caps;
  getfilled_cap_list() {
    let temp={};
    temp["product_code"]=this.selectedResult['product_code'],
    temp["batch_number"]=this.selectedResult['batch_number'],
    temp["work_order_no"]=this.selectedResult['work_order_no'],
    temp["DocumentNo"]=this.DocumentNo
    this.service.post('bmr/bmr.php?type=getfilled_cap_list',JSON.stringify(temp)).subscribe(response => {
      this.filled_caps = response;
    });
  }
  disp_dtl;
  ar_datas;
  Get_ar_data(value) {
    
     
    this.service.get('store/dispensing.php?type=Get_ar_data&material_code='+value).subscribe(response => {
      this.ar_datas = response;
    });
  }
  get_bmr_disp_dtl() {
    
     
    this.service.get('bmr/bmr.php?type=get_bmr_disp_dtl&work_id='+this.selectedResult['id'] ).subscribe(response => {
      this.disp_dtl = response;
    });
  }
  // selected_dispIndex:any=[];
  // get_disp_data_i(index){
  //   this.selected_dispIndex=this.disp_dtl[index-1]
  //   console.log('selected_dispIndex :>> ', this.selected_dispIndex);
  // }
  selected_dispIndex: any= [];
  selected_dispIndex1: any= [];
  selected_disop_mater;

get_disp_data_i(index: number) {
  if (index > 0 && index <= this.disp_dtl.length) {
    this.selected_dispIndex = [this.disp_dtl[index - 1]];
    this.selected_dispIndex1 = this.selected_dispIndex[0];
  } else {
    this.selected_dispIndex = [];
  }
  console.log('selected_dispIndex :>> ', this.selected_dispIndex);
  this.Get_ar_data(this.selected_disop_mater);
}
  enapsulation_list=[];
  saveEncapsulation_Running_Record(data){
    let temp=data.value;
    this.enapsulation_list[this.enapsulation_list.length]=temp;
    console.log(this.enapsulation_list)
    data.resetForm();
  }
  deleteEncapsulation_Running_Record(index){
    this.enapsulation_list.splice(index,1)
    console.log(this.enapsulation_list)
     
  }
  empy_rm_wt_list=[]
  addemptyrawbagweight(data){
    let temp=data.value;
    this.empy_rm_wt_list[this.empy_rm_wt_list.length]=temp;
    console.log(this.empy_rm_wt_list)
    data.resetForm();
  }
  deleteaddemptyrawbagweight(index){
    this.empy_rm_wt_list.splice(index,1)
    console.log(this.empy_rm_wt_list)
     
  }
  qcSample_form_list=[];
  addqc_sample_wt_list(data){
    let temp=data.value;
    this.qcSample_form_list[this.qcSample_form_list.length]=temp;
    console.log(this.qcSample_form_list)
    data.resetForm();
  }
  deleteqc_sample_wt_list(index){
    this.qcSample_form_list.splice(index,1)
    console.log(this.qcSample_form_list)
     
  }
  filled_cap_list=[];
  addfilled_cap_weight(data){
    let temp=data.value;
    this.filled_cap_list[this.filled_cap_list.length]=temp;
    console.log(this.filled_cap_list)
    data.resetForm();
  }
  deletefilled_cap_weight(index){
    this.filled_cap_list.splice(index,1)
    console.log(this.filled_cap_list)
     
  }


}
