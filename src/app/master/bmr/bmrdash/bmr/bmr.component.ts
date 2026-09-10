import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router, Params } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DataService } from '../data.service';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';

declare let alertify;

@Component({
  selector: 'app-bmr',
  templateUrl: './bmr.component.html',
  styleUrls: ['./bmr.component.css']
})
export class BmrComponent implements OnInit, OnDestroy {
  /** Teardown subject for subscription cleanup; prevents leaks and updates after destroy. */
  private readonly destroy$ = new Subject<void>();
purpose;
product_code;
Process_Title;
tables:any=[];
isMain=true;

closeOffcanvas(): void {
  this.isNewStage2 = false;
}
init = ({
  
  menubar: false,
  statusbar: false,
  content_style: 'body { font-size: 12pt; font-family: Times; } ol{counter-reset: item}ol > li{display: block}ol > li:before {content: counters(item, ".") ". ";counter-increment: item}',
  plugins: [
    'table'
  ],
  toolbar:
    'table',

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
    },
  
  contextmenu: 'false', // Disable the context menu for tables
  table_toolbar: false // Disable the cell editing popup
});

  api = 'pyc83d69ldr5hmcctu5rdnz66b498vz3nf5wofapfe87o13a';
  // api = 'lzydfz1lgit47bg7tnab31cq8uxwfdnetk1vo3lqxe70so59';



  // editormodules = {
  //   toolbar: [
  //     ['bold', 'italic', 'underline'], // toggled buttons
  //     ['blockquote', 'code-block'],

  //     [{ header: 1 }, { header: 2 }], // custom button values
  //     [{ list: 'ordered' }, { list: 'bullet' }],
  //     [{ script: 'sub' }, { script: 'super' }], // superscript/subscript
  //     [{ indent: '-1' }, { indent: '+1' }], // outdent/indent
  //     [{ direction: 'rtl' }], // text direction

  //     [{ size: ['small', false, 'large', 'huge'] }], // custom dropdown
  //     [{ header: [1, 2, 3, 4, 5, 6, false] }],

  //     [{ color: [] }, { background: [] }], // dropdown with defaults from theme
  //     [{ font: [] }],
  //     [{ align: [] }],
  //     [{ table: true }],

  //     ['clean'], // remove formatting button
  //     ['link', 'image', 'video'],
  //   ]
  // };



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
  options = [
    { "name": "QA","option": "qa", "status": false, "list": []},
    { "name": "Production","option": "production", "status": false, "list": []}
  ];
  options2 = [
    { "name": "Procedure","option": "Procedure", "status": false, "list": []},
    { "name": "Instruction","option": "Instruction", "status": false, "list": []}
  ];
  options1 = [
    { "name": "Equipments / Instruments","option": "equipment", "status": true, "list": []}
   ];
  units;
  isnewIpqcForm = false;

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
  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router,private dataService: DataService) { 
    this.plant_type = this.service.getPlantConfigFields('plant_type');

  }

  ngOnInit() {
    this.getManufacturingStages();
    this.getsampleWashWater();
    this.getequipments();
    this.getRoom();
    this.getDosages();
    this.getRoomChecklist();
    this.getSpecification();
    this.getSection();
    this.getUnits();
    this.getApprovedLabors();
    this.getIPQC();
    this.getDosageTypes();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }


  
  tableData: any[][] = [
    ['A', 'B'],
    ['C', 'D']
  ];

  addRow() {
    this.tableData.push(new Array(this.tableData[0].length).fill(''));
  }

  addColumn() {
    this.tableData.forEach(row => row.push(''));
  }

    
  




  getUnits() {
    this.service.get('bmr_new/common.php?type=getUnits').pipe(takeUntil(this.destroy$)).subscribe(response => {
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
  checklistListdesp=[];
  addDatadesp(data) {
    // let list = this.options[index].list;
    // list[list.length] = this.ch_list;
    // this.ch_list = '';
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.checklistListdesp[this.checklistListdesp.length] = temp;
    console.log(this.checklistListdesp);
    data.resetForm();
  }
  delData(index) {
    this.checklistList.splice(index, 1);
  }
  delDatadesp(index) {
    this.checklistListdesp.splice(index, 1);
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
  prod_checklistListdesp=[];
  addDataProddesp(data) {
    // let list = this.options[index].list;
    // list[list.length] = this.ch_list;
    // this.ch_list = '';
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.prod_checklistListdesp[this.prod_checklistListdesp.length] = temp;
    console.log(this.prod_checklistListdesp);
    data.resetForm();
  }
  delDataProd(index) {
    this.prod_checklistList.splice(index, 1);
  }
  delDataProddesp(index) {
    this.prod_checklistListdesp.splice(index, 1);
  }
  /** Loading and error state for main product list (avoids undefined/glitches in template). */
  loadingResults = false;
  loadErrorResults: string | null = null;

  getManufacturingStages() {
    this.loadingResults = true;
    this.loadErrorResults = null;
    this.service.get('bmr_new/product.php?type=getBrandProductsLogBMR').pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loadingResults = false;
        if (this.selectedIndex !== -1 && this.results.length > this.selectedIndex) {
          this.start(this.selectedIndex);
          if (this.MainselectedStageIndex !== -1) {
            this.selectStagePage(this.MainselectedStageIndex);
          }
        }
      },
      error: (err) => {
        this.loadingResults = false;
        this.loadErrorResults = err?.message || 'Failed to load products. Please try again.';
        this.results = [];
      }
    });
  }

  /** trackBy for product list – improves *ngFor performance and avoids re-render glitches. */
  trackByManufacturingId(_index: number, item: any): string | number {
    return item?.manufacturing_process_id ?? _index;
  }

  /** Generic trackBy by index for stable list identity. */
  trackByIndex(index: number): number {
    return index;
  }



  getsampleWashWater() {
    this.service.get('qa/washwater.php?type=getsampleWashWater').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.water = response;
    });
  }

  getSpecification() {
    this.service.get('bmr_new/stages.php?type=getSpecificationsLog&dosage_form=&grade=&status=').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.spec = response;
    });
  }
  log_books;
  get_logbook(value: string) {
    this.service.get('bmr_new/equipments.php?type=get_logbook_data_by_type&activity=' + value).pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.log_books = response;
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
  start(index){
    // this.selectedwater =this.water[index];
    // if(this.processes1.length==0){
      this.selectedIndex = index;
      this.selectedResult = this.results[index];
      this.isStart = true;
      console.log(this.selectedResult)
 
    this.getInstrunctions();
    this.getAbbrivation();
   this.getbmrEquipments();
   this.getbmrRooms();
   this.getProcedure();
   this.getProcessStage(this.selectedResult['manufacturing_process_id']);
   this.getSpec_date(this.selectedResult['product_code']);
   
  }
  Review(id: string, index: number) {
    const selectedResult = this.results[index];
    this.dataService.setData(selectedResult);
    
      this.router.navigate(['..', 'BmrReview', id], { relativeTo: this.route });
  
     
  }
  Approval(id: string, index: number){
    const selectedResult = this.results[index];
    this.dataService.setData(selectedResult);
    
   
      this.router.navigate(['..', 'Bmrapproval', id], { relativeTo: this.route });

  }
  bmr_view(id: string, index: number){
    const selectedResult = this.results[index];
    this.dataService.setData(selectedResult);
    
   
      this.router.navigate(['..', 'BmrView', id], { relativeTo: this.route });

  }
  spec_data;
  spc_no;
  spc_tests:any=[];
  getSpec_date(data){
    this.service.get('bmr_new/stages.php?type=getSpec_date&material_code='+data).subscribe(response=>{
      this.spec_data = response;
      // this.spc_no=this.spec_data[0]['specification_no']
      if (this.spec_data && this.spec_data.length > 0) {
        this.spc_no = this.spec_data[0]['specification_no'];
        this.spc_tests=this.spec_data[0]['tests'];
    } else {
        console.log('spec_data is undefined or empty');
        // Handle the error, for example, by setting a default value or showing a message
        this.spc_no = 0;
    }
    });
  }

  Limit;
  get_spec_test_data(index){
    this.Limit=this.spc_tests[index-1]['limits']
  }
  getIPQC() {
    this.service.get('bmr_new/ipqc.php?type=getIPQC').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.ipqccheck = response;
    });
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }
  getequipments(){
    this.service.get('bmr_new/equipments.php?type=getEquipmentNames').subscribe(response =>{
      this.equipments = response;
    });
  }
  bmr_rooms;
  getRoom() {
    this.service.get('bmr_new/section.php?type=getSections&department1=Production').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.bmr_rooms = response;
    });
  }

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
  getEquipmentDetails(index){
    this.selectedEquipment_data=this.equipments[index-1]
   
  }
  selectedEquipment_data2=[];
  getEquipmentDetails2(index){
    this.selectedEquipment_data2=this.eqDatas[index-1]
    console.log(this.selectedEquipment_data2);
   
  }
  bmrRoomList=[];
  addbmrTempRoom(data){
    let temp=data.value;
    temp['room_name']=this.selectedbmr_rooms2['room_name'];
    temp['room_code']=this.selectedbmr_rooms2['room_code']
    temp['log_no']=this.selectedbmr_rooms2['log_no']

    // this.bmrRoomList[this.bmrRoomList.length]=temp
    this.room[this.room.length]=temp
    console.log(this.bmrRoomList);
  }
  bmractionRoomList=[];
  addbmrTempactionRoom(data){
    let temp=data.value;
    temp['room_name']=this.selectedbmr_rooms22['room_name'];
    temp['room_code']=this.selectedbmr_rooms22['room_code']
    temp['log_no']=this.selectedbmr_rooms22['log_no']
    this.roomActions.push(temp);
    // this.roomAction[this.roomAction.length]=temp;
    console.log(this.roomActions);
}

  CleaStatusList=[];
  addCleaStatus(data){
    let temp=data.value;
   this.CleaStatusList[this.CleaStatusList.length]=temp
    console.log(this.CleaStatusList);
  }

  addEquipment(data) {  
    // if (!data.valid) {
    //   alert('All fields are required');
    //   return;
    // }
    // let temp = data.value;
    let temp ={};

    
   
    temp['equipment_name']=this.selectedEquipment_data['equipment_name'];
    temp['equipment_code']=this.selectedEquipment_data['equipment_code'];
    temp['manufacturing_process_id'] = this.selectedResult['id']
    this.service.post('bmr_new/ebmr.php?type=addbmr_Equipments',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = response['instructions'];
        this.getbmrEquipments()
        // data.resetForm();
        alertify.success('SAVE');
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  addBmrRoom(data) {  
       let temp ={};   
   
    temp['section_name']=this.selectedbmr_rooms['section_name'];
    temp['section_code']=this.selectedbmr_rooms['section_code'];
    temp['log_no'] = this.selectedbmr_rooms['log_no']
    this.service.post('bmr_new/ebmr.php?type=addbmrRooms',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = response['instructions'];
        this.getbmrRooms()
        // data.resetForm();
        alertify.success('SAVE');
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  eqdatas2=[];
  addEquipment2() {  

    let temp ={};      
    temp['equipment_name']=this.selectedEquipment_data2['equipment_name'];
    temp['equipment_code']=this.selectedEquipment_data2['equipment_code'];
    // this.eqdatas2[this.eqdatas2.length]=temp;
    this.equipmentsss[this.equipmentsss.length]=temp;
    console.log(this.eqdatas2);
  }
  CleaningChecksdatas2=[];
  addCleaningChecks(data) {  

    let temp =data.value;      
    temp['equipment_name']=this.selectedEquipment_data2['equipment_name'];
    temp['equipment_code']=this.selectedEquipment_data2['equipment_code'];
    this.CleaningChecks[this.CleaningChecks.length]=temp;
    console.log(this.CleaningChecks);
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
  ProcessTitle;
  DocumentNo;
  Forms;

  saveProcess(){
    let temp={};
    temp['product_code']=this.product_code;
    temp['Forms']=this.Forms;
    temp['for_department']=this.for_department;
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

  // getProcesses(){
  //   this.service.get('bmr_new/process.php?type=getProcesses').subscribe(response =>{
  //     this.stagemaster =response;
      
  //   });
    
  // }
  // getProcesses(){
  //   this.service.get('bmr_new/process.php?type=getProcesses').subscribe(response =>{
  //     this.stagemaster =response;
  //   });
  // }
  sections;
  getSection() {
    this.service.get('bmr_new/common.php?type=getDepartmentSections&department1=Production').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.sections = response;
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
  getStageMasterDetails(index){
   
      this.selectStage = this.stagemaster[index];
      this.select_Stage = this.selectStage['process_types'];
  
  }
  S_stage;
  selectedStageIndex = -1;

  isEncapsule=false;
  selectPage(index, index1) {
    this.selectedPage = index;
    if(index==13){
      this.isEncapsule=true;
      console.log('hellllo')
    }
    // this.S_stage=this.selectedResult['Stages'][index1];
    // if (index > 9 && index1 !== -1) {
    //   let stages = this.selectedResult['Stages'];
    //   this.selectedStage = stages[index1];
    //   this.selectedStageIndex = index;
    // } else {
    //   this.selectedStage = [];
    // }
    // console.log(this.selectedPage['id'])
    //  console.log(this.selectedStage['stages'].id);
    //  console.log(this.selectedPage);
    //  console.log(this.selectedResult['stages'][index]);
    //  console.log(this.S_stage['id']);
     this.getApprovedLabors();
    this.stepsss=false;
     
  }
  stage_index;
  stepsss=false;
  MainselectedStageIndex=-1;
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
    this.service.post('bmr_new/ipqc.php?type=saveIPQC', JSON.stringify(temp)).subscribe(response => {
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
  eqDatas ;
  eqDataList=[];




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
  delCleaStatusList(index) {
    this.CleaStatusList.splice(index, 1);
  }

  deleteeqips(index) {
    // this.eqdatas2.splice(index, 1);
    this.equipmentsss.splice(index, 1);
    console.log(this.eqdatas2)
  }
  delCleaningChecksdatas2(index) {
    this.CleaningChecks.splice(index, 1);
  }


  saveInstruction(data){
    if (!data.valid) {
      return;
    }
    let temp = data.value;

    temp['instruction'] = this.instructionList;
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    this.service.post('bmr_new/stages.php?type=saveInstruction',JSON.stringify(temp)).subscribe(response => {
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
   
  }
  delInstructiondesp(index) {
    this.instructionListdesp.splice(index, 1);
   
  }

  
  saveEnv(){
    let temp = {}
    temp['checkpoint'] = this.environmentList;
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    // temp['id'] = this.selectedStage['id'];
    // temp['env_frequency'] = this.envfrequency;
    // temp['env_freq_unit'] = this.fre_unit;
    // temp['env_qa'] = this.env_qa;
    // temp['id'] = this.S_stage['id'];

    this.service.post('bmr_new/stages.php?type=saveEnvironment',JSON.stringify(temp)).subscribe(response => {
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



  saveSieveInteggity(data,type){

    let temp = {};
   
     
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveSieveInteggity',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  
        this.getManufacturingStages();
        }
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });  

  }
  saveEquipment(data,type){

    let temp = {};
   
    temp['equipment'] = this.equipmentsss;
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveEquipment',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  
        this.getManufacturingStages();
        }
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });  

  }
  saveYeilds(data,type){

    let temp = {};
   
 
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveYeilds',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  
        this.getManufacturingStages();
        }
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });  

  }
  saveCleaningChecks(data,type){

    let temp = {};
   
    temp['CleaningChecksdatas2'] = this.CleaningChecks;
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;

  
    this.service.post('bmr_new/stages.php?type=saveCleaningChecks',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
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
    this.service.post('bmr_new/stages.php?type=deleteEquipment&id=' + this.selectedStage['id'], JSON.stringify(equipment_name)).subscribe(response => {
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
  form_no_list:any=[];
  add_logbook(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.form_no_list[this.form_no_list.length] = temp;
   
    data.resetForm();
  }

  addProcedure(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.procedures[this.procedures.length] = temp;
    this.procedureList[this.procedureList.length] = temp;
    data.resetForm();
  }
  // addLineClearance(data) {
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.LineClearance[this.LineClearance.length] = temp;
  //   this.LineClearanceList[this.LineClearanceList.length] = temp;
  //   data.resetForm();
  // }
  addLineClearance(data: any) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    let temp = data.value;
  
    // Ensure that LineClearance and LineClearanceList are arrays
    if (!Array.isArray(this.LineClearance)) {
      this.LineClearance = [];
    }
  
    if (!Array.isArray(this.LineClearanceList)) {
      this.LineClearanceList = [];
    }
  
    // Use push method to add the new item
    this.LineClearance.push(temp);
    this.LineClearanceList.push(temp);
  
    data.resetForm();
  }
  procedureListdesp=[];
  addProceduredesp(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.procedureListdesp[this.procedureListdesp.length] = temp;
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
  instructionListdesp=[];
  despaddInstruction(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.instructionListdesp[this.instructionListdesp.length] = temp;
    data.resetForm();
  }


  delform_no_list(index) {
    // this.procedureList.splice(index, 1);
    this.form_no_list.splice(index, 1);
  }
  delProcedure(index) {
    // this.procedureList.splice(index, 1);
    this.procedures.splice(index, 1);
  }
  deladdLineClearance(index) {
    // this.procedureList.splice(index, 1);
    this.LineClearance.splice(index, 1);
  }
  delbmrRoomList(index) {
    this.room.splice(index, 1);
    // this.bmrRoomList.splice(index, 1);
  }
  delbmractionRoomList(index) {
    this.roomActions.splice(index, 1);
  }
  delProceduredesp(index) {
    this.procedureListdesp.splice(index, 1);
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
  RoomClearanceCheckList;
  Form_no;
  version_no;
  specification_no;
  effective_date;
  addRoomCheck(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['Form_no']=this.Form_no
    temp['version_no']=this.version_no
    temp['specification_no']=this.specification_no
    temp['effective_date']=this.effective_date
    this.service.post('bmr_new/stages.php?type=addRoomCheck',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
       this.getRoomChecklist();
       
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
    data.resetForm();
  }
  addLineCheck(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['Form_no']=this.Form_no
    temp['version_no']=this.version_no
    temp['specification_no']=this.specification_no
    temp['effective_date']=this.effective_date
    this.service.post('bmr_new/stages.php?type=addLineCheck',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
       this.getLineChecklist();
       
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
    data.resetForm();
  }
  getRoomChecklist() {
    this.service.get('bmr_new/stages.php?type=getRoomChecklist').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.RoomClearanceCheckList = response;
    });
  }
  LineClearanceCheckList;
  getLineChecklist() {
    this.service.get('bmr_new/stages.php?type=getLineChecklist')
    .subscribe(response => {
      this.LineClearanceCheckList = response;
    });
    console.log(this.labors);
  }
  getApprovedLabors() {
    this.service.get('bmr_new/common.php?type=getprodOperatorsWorkerList').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.labors = response;
    });
  }
  instructions;
  // getInstrunctions() {
  //   this.service.get('bmr_new/ebmr.php?type=get_Instruction&product_code='+this.selectedResult['product_code'])
  //   .subscribe(response => {
  //     this.instructions = response;
  //   });
  //   console.log(this.labors);
  // }
  getInstrunctions(){
    this.service.get('bmr_new/ebmr.php?type=get_Instruction&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.instructions=response;
    });
  }
  Proceduresss;
  getProcedure(){
    this.service.get('bmr_new/ebmr.php?type=get_Procedure&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.Proceduresss=response;
    });
  }
  abbreviation;
  getAbbrivation(){
    this.service.get('bmr_new/ebmr.php?type=getAbbrivation&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.abbreviation=response;
    });
  }
  getbmrEquipments(){
    this.service.get('bmr_new/ebmr.php?type=getbmrEquipments&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.eqDatas=response;
    });
  }
  bmrRoomDatas
  getbmrRooms(){
    this.service.get('bmr_new/ebmr.php?type=getbmrRooms&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.bmrRoomDatas=response;
    });
  }
  RoomDatas;
  getbmrRoom(){
    this.service.get('bmr_new/ebmr.php?type=addbmrRooms&manufacturing_process_id='+this.selectedResult['id'])
    .subscribe(response =>{
      this.RoomDatas=response;
    });
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
    this.weighings[this.weighings.length] = temp;
    data.resetForm();
  }
  QcSAMPS:any=[];
  addQcSAMPForm(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.QcSAMPS[this.QcSAMPS.length] = temp;
    data.resetForm();
  }

  delWeighings(index) {
    this.weighings.splice(index, 1);
  }
  delQcSAMPS(index) {
    this.QcSAMPS.splice(index, 1);
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
  sequnce;
  seq_list:any=[];
  combinedList:any=[];
  // makeSeq(){
  //   let temp={}
  //   temp["list"]=this.sequnce
  //   this.seq_list[this.seq_list.length]=temp;
  //   this.sequnce='';
  //   console.log(this.seq_list);
  //   this.saveSeq();
   
  // }
  disabledOptions: string[] = [];
  makeSeq() {
    if (!this.seq_list) {
        this.seq_list = []; // Initialize seq_list as an empty array if it's null
    }
    let temp = {};
    temp["list"] = this.sequnce;
    this.seq_list.push(temp); // Use push method to add elements to the array
    this.sequnce = '';
    console.log(this.seq_list);
     // Disable the selected option
     this.disabledOptions.push(temp["list"]);

    const combinedArray = [...this.seq_list];
    this.combinedList = combinedArray;
    console.log(this.combinedList);
    // console.log('this.seq_list :>> ', this.seq_list);
    this.saveSeq()
}

  saveSeq(){
    let temp={}
    

    temp['list'] = this.seq_list;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
  

    this.service.post('bmr_new/stages.php?type=save_Sequence',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
     
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  delcombinedList(index) {
    this.seq_list.splice(index, 1);
    this.saveSeq();
    console.log(this.seq_list)
}
  saveInitialChecks(){
  
    let temp = {};
    temp['checkpoint'] = this.initialCheckList;
    // temp['id'] = this.S_stage['id'];
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    this.service.post('bmr_new/stages.php?type=saveInitialCheck',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
       
       
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
    this.service.post('bmr_new/stages.php?type=',JSON.stringify(temp)).subscribe(response => {
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

  
  savemillinsifting(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=savemillinsifting',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveDispensing(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveDispensing',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveBlendLubrication(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveBlendLubrication',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveMixing(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveMixing',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveYIELD_RECONCILIATION(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveYIELD_RECONCILIATION',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveINPROCESSYIELD(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveINPROCESSYIELD',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveDry_SIFTING_MILLING(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveDry_SIFTING_MILLING',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveCOMPRESSION_PARAMETERS(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveCOMPRESSION_PARAMETERS',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveWeighing_Variation_Recoed(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveWeighing_Variation_Recoed',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveDrying(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveDrying',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveSIFTING_Lubrication(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveSIFTING_Lubrication',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveWeighings(data,type){
 
    let temp ={};
      temp['weighings'] = this.weighings;
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveWeighing',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }
  saveQcSample(data,type){
    // if (!data.valid) {
    //   return;
    // }
    let temp ={};
    // if(this.isweighing == "NO"){
    // if(this.isDrying == "NO"){
    // if(this.isBlendLubrication == "NO"){
    // if(this.isWeighing_Variation_Recoed == "NO"){
    // if(this.isCOMPRESSION_PARAMETERS == "NO"){
    // if(this.isINPROCESS_YIELD == "NO"){
    // if(this.isYIELD_RECONCILIATION == "NO"){
    //   temp['weighings'] = '';  
    // }else{
      temp['QcSAMPS'] = this.QcSAMPS;
    // }
    // temp['id'] = this.selectedStage['id'];  
    // temp['weighing_production'] = this.weighing_production;
    // temp['weighing_qa'] = this.weighing_qa;
    
    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];

    temp['type'] = type;

  

    this.service.post('bmr_new/stages.php?type=saveQcSample',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success') {
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      } else{
        alertify.error("Error to save Record !!!")
      }
    });
  }

  stage_lists: any[] = [];

addGeneralListStage(data, index,id) {
    if (!data.valid) {
        return;
    }
    let temp = data.value;
    temp['stageName'] = this.selectedStage['stages'];
    temp['step_id'] = id;

    if (!this.stage_lists[index]) {
        this.stage_lists[index] = [];
    }
    this.stage_lists[index].push(temp);

    // Reset the form after adding the instruction
    console.log(this.stage_lists);
    data.resetForm();
}

saveStageList() {

  let temp = {};
  temp['stage_lists'] = this.stage_lists;
  // temp['stage_id'] = this.selectedStage['id'];
  // temp['step_id'] = this.selectedStage['id'];

  this.service.post('bmr_new/process.php?type=saveStageList',JSON.stringify(temp)).subscribe(response =>{
    if(response['status']=='success') {
      alertify.success('Save');
    } else{
      alertify.error('Error');
    }
  });
}
selected_step=[];
isNewStage2=false;
isNewStages(index){
  this.isNewStage = true;
  this.selected_step=this.selectedStage['Steps'][index]
}
isNewdesp=false;
isDesp(){
  this.isNewdesp = true;
 
}
steps2;
steps22;
instruction;
procedures:any=[];
Qa_LINE_clearance;
prod_LINE_clearance;
equipmentsss:any=[];
environmentsss;
weighings:any=[];
initial_checks;
inprocess;
sequence;


isroom;
isCleaningChecks;
isroomActions;
isEquipmemntCleaning;
istable;
isFraction;
isInprocessChecks;
ismillinsifting;
isDispensing;
isMixing;
isSiftLubrication;
isDry_SIFTING_MILLING;
isLineClearance= '';
isSieveInteggity= '';
isYield= '';
;
// isQcSample;
isFormats;
isQaReview;
room:any=[];
roomActions:any=[];
// roomActions;
EquipmemntCleaning;
// tables;

Formats;
CleaningChecks:any=[];;
QaReview;
procedure_confirm_by;
procedure_confirm_on;
room_confirm_by;
room_confirm_on;
equipment_confirm_by;
equipment_confirm_on;
CleaningChecks_confirm_by;
CleaningChecks_confirm_on;
roomActions_confirm_by;
roomActions_confirm_on;
EquipmemntCleaning_confirm_by;
EquipmemntCleaning_confirm_on;
tables_confirm_by;
tables_confirm_on;
weighing_status_confirm_by;
weighing_status_confirm_on;
LineClearance_confirm_by;
LineClearance_confirm_on;
QcSample_confirm_by;
QcSample_confirm_on;
Logbook_confirm_by;
Logbook_confirm_on;
NewStages2Step
isNewStages2Index=-1;

SetIndex(){
  this.isNewStages2Index=-1;
}



isNewStages2(index,step){
  this.isNewStages2Index=index;
  this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook;
  this.istable='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  this.NewStages2Step=step;

  this.isStart = false;
 

  this.selected_step=this.selectedStage['Steps'][index]
  this.isNewStage2 = true;
 

  this.service.get('bmr_new/stages.php?type=GET_bmr_stages_steps&stpe_id='+this.selected_step['id']+'&Stage_id='+this.selectedStage['id']).subscribe(response =>{
    this.steps2 =response;
    //  this.instruction=JSON.parse(this.steps2[0]['instructions']);
     this.procedures=this.steps2[0]['procedures'];
     this.LineClearance=this.steps2[0]['LineClearance'];
     this.form_no_list=this.steps2[0]['Logbook'];
     this.room=this.steps2[0]['room'];
     this.equipmentsss=this.steps2[0]['equipment'];
     this.weighings=this.steps2[0]['weighing'];
     this.CleaningChecks=this.steps2[0]['CleaningChecks'];
     this.roomActions=this.steps2[0]['roomActions'];
     this.EquipmemntCleaning=this.steps2[0]['EquipmemntCleaning'];
     this.tables=this.steps2[0]['tables'];
     this.QcSAMPS=this.steps2[0]['QcSample'];
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
     this.isFraction=this.steps2[0]['isFraction'];
      this.isInprocessChecks=this.steps2[0]['isInprocessChecks'];
      this.ismillinsifting=this.steps2[0]['ismillinsifting'];
      this.isDispensing=this.steps2[0]['isDispensing'];
     this.isMixing=this.steps2[0]['isMixing'];
     this.isSiftLubrication=this.steps2[0]['isSiftLubrication'];
     this.isDry_SIFTING_MILLING=this.steps2[0]['isDry_SIFTING_MILLING'];
     this.isLineClearance=this.steps2[0]['isLineClearance'];
     this.isLineClearance=this.steps2[0]['isLineClearance'];
     this.isSieveInteggity=this.steps2[0]['isSieveInteggity'];
      this.isYield=this.steps2[0]['isYield'];
     this.isweighing=this.steps2[0]['isweighing'];
 
      this.isDrying=this.steps2[0]['isDrying'];
     this.isBlendLubrication=this.steps2[0]['isBlendLubrication'];
      this.isWeighing_Variation_Recoed=this.steps2[0]['isWeighing_Variation_Recoed'];
     this.isCOMPRESSION_PARAMETERS=this.steps2[0]['isCOMPRESSION_PARAMETERS'];
     this.isINPROCESS_YIELD=this.steps2[0]['isINPROCESS_YIELD'];
     this.isYIELD_RECONCILIATION=this.steps2[0]['isYIELD_RECONCILIATION'];
     this.isQcSample=this.steps2[0]['isQcSample'];
      this.isLogbook=this.steps2[0]['isLogbook'];
     this.isFormats=this.steps2[0]['isFormats'];
     this.isQaReview=this.steps2[0]['isQaReview'];
     
     this.procedure_confirm_by=this.steps2[0]['procedure_confirm_by'];
     this.procedure_confirm_on=this.steps2[0]['procedure_confirm_on'];
     this.room_confirm_by=this.steps2[0]['room_confirm_by'];
     this.room_confirm_on=this.steps2[0]['room_confirm_on'];
     this.equipment_confirm_by=this.steps2[0]['equipment_confirm_by'];
     this.equipment_confirm_on=this.steps2[0]['equipment_confirm_on'];
     this.CleaningChecks_confirm_by=this.steps2[0]['CleaningChecks_confirm_by'];
     this.CleaningChecks_confirm_on=this.steps2[0]['CleaningChecks_confirm_on'];
     this.roomActions_confirm_by=this.steps2[0]['roomActions_confirm_by'];
     this.roomActions_confirm_on=this.steps2[0]['roomActions_confirm_on'];
     this.EquipmemntCleaning_confirm_by=this.steps2[0]['EquipmemntCleaning_confirm_by'];
     this.EquipmemntCleaning_confirm_on=this.steps2[0]['EquipmemntCleaning_confirm_on'];
     this.tables_confirm_by=this.steps2[0]['tables_confirm_by'];
     this.tables_confirm_on=this.steps2[0]['tables_confirm_on'];
     this.weighing_status_confirm_by=this.steps2[0]['weighing_status_confirm_by'];
     this.weighing_status_confirm_on=this.steps2[0]['weighing_status_confirm_on'];
     this.LineClearance_confirm_by=this.steps2[0]['LineClearance_confirm_by'];
     this.LineClearance_confirm_on=this.steps2[0]['LineClearance_confirm_on'];
     this.QcSample_confirm_by=this.steps2[0]['QcSample_confirm_by'];
     this.QcSample_confirm_on=this.steps2[0]['QcSample_confirm_on'];
     this.Logbook_confirm_by=this.steps2[0]['Logbook_confirm_by'];
     this.Logbook_confirm_on=this.steps2[0]['Logbook_confirm_on'];















     console.log('this.isprocedure=',this.isprocedure)
     console.log('this.isroom=',this.isroom)
     console.log('this.isequipment=',this.isequipment)
     console.log('this.isCleaningChecks=',this.isCleaningChecks)
     console.log('this.isroomActions=',this.isroomActions)
     console.log('this.isEquipmemntCleaning=',this.isEquipmemntCleaning)
     console.log('this.istable=',this.istable)
     console.log('this.isFraction=',this.isFraction)
     console.log('this.isFraction=',this.isFraction)
      console.log('this.isInprocessChecks=',this.isInprocessChecks)
      console.log('this.ismillinsifting=',this.ismillinsifting)
      console.log('this.isDispensing=',this.isDispensing)
     console.log('this.isMixing=',this.isMixing)
     console.log('this.isSiftLubrication=',this.isSiftLubrication)
     console.log('this.isDry_SIFTING_MILLING=',this.isDry_SIFTING_MILLING)
     console.log('this.isLineClearance=',this.isLineClearance)
     console.log('this.isSieveInteggity=',this.isSieveInteggity)
      console.log('this.isYield=',this.isYield)
     console.log('this.isweighing=',this.isweighing)
      console.log('this.isDrying=',this.isDrying)
      console.log('this.isBlendLubrication=',this.isBlendLubrication)
     console.log('this.isWeighing_Variation_Recoed=',this.isWeighing_Variation_Recoed)
     console.log('this.isCOMPRESSION_PARAMETERS=',this.isCOMPRESSION_PARAMETERS)
     console.log('this.isINPROCESS_YIELD=',this.isINPROCESS_YIELD)
     console.log('this.isYIELD_RECONCILIATION=',this.isYIELD_RECONCILIATION)
     console.log('this.isQcSample=',this.isQcSample)
     console.log('this.isFormats=',this.isFormats)
     console.log('this.isQaReview=',this.isQaReview)





     
     //  sequence (guard against null/undefined/invalid so seq_list is always an array)
     try {
       const rawSeq = this.steps2[0] && this.steps2[0]['sequence'];
       const parsedSeq = typeof rawSeq === 'string' ? JSON.parse(rawSeq) : rawSeq;
       this.seq_list = (parsedSeq != null && Array.isArray(parsedSeq)) ? parsedSeq : [];
     } catch {
       this.seq_list = [];
     }
     console.log('this.seq_list :>> ', this.seq_list);
     this.disabledOptions=[];
    //  console.log(this.sequence)
  });
  
}
getSeqListValues() {
  return (this.seq_list && Array.isArray(this.seq_list)) ? this.seq_list.map(item => item.list) : [];
}
/** True when step flag is on (API may return 1 or '1'). Use in template so options show. */
isStepFlagOn(value: any): boolean {
  return value === 1 || value === '1' || value === true;
}
isequipment;
isinitial;
isinprocess;

  
  addGeneralList(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    temp['product_code'] = this.selectedResult['product_code']
    temp['manufacturing_process_id'] = this.selectedResult['id']
    this.service.post('bmr_new/ebmr.php?type=addInstruction',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = response['instructions'];
        this.getInstrunctions()
        data.resetForm();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  addprocGeneralList(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    temp['product_code'] = this.selectedResult['product_code']
    temp['manufacturing_process_id'] = this.selectedResult['id']
    this.service.post('bmr_new/ebmr.php?type=addProcedure',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = response['instructions'];
        this.getProcedure()
        data.resetForm();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  deleteGeneralInstruction(id) {
    // let instructions = this.selectedResult['instructions'];
    // instructions.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=deleteInstruction&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = instructions;
        this.getInstrunctions();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  deleteGeneralProceduresss(id) {
    // let instructions = this.selectedResult['instructions'];
    // instructions.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=deleteProceduresss&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = instructions;
        this.getProcedure();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  deleteeq(id) {
    // let instructions = this.selectedResult['instructions'];
    // instructions.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=deletebmrEquipments&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = instructions;
        this.getbmrRooms();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  deleteBmrRoom(id) {
    // let instructions = this.selectedResult['instructions'];
    // instructions.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=deleteBmrRoom&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = instructions;
        this.getInstrunctions();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  delRoomClaCheck(id) {
    // let instructions = this.selectedResult['instructions'];
    // instructions.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=delRoomClaCheck&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = instructions;
        this.getRoomChecklist();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  delLineClaCheck(id) {
    // let instructions = this.selectedResult['instructions'];
    // instructions.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=delLineClaCheck&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = instructions;
        this.getLineChecklist();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  deleteGeneralInstruction1(instructionIndex: number, sublistIndex: number) {
    // Assuming stage_lists is a property in your component containing the data
    // Remove the instruction from the sublist using splice
    this.stage_lists[sublistIndex].splice(instructionIndex, 1);
}

  addAbbreviationList(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp=data.value;    
    // temp['abbreviation'] = data.value;
    // temp['product_code'] = this.selectedResult['product_code'];
    temp['manufacturing_process_id'] = this.selectedResult['id'];
    this.service.post('bmr_new/ebmr.php?type=addAbbreviation',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        this.getAbbrivation()
        data.resetForm();
        alertify.success(response['msg']);
      } else{
        alertify.error(response['msg']);
      }
    });
  }

  deleteAbbreviationList(id) {
    // let abbreviation = this.selectedResult['abbreviation'];
    // abbreviation.splice(index, 1);
    let temp={}
    this.service.post('bmr_new/ebmr.php?type=deleteAbbreviation&id=' + id, JSON.stringify(temp)).subscribe(response => {
      if(response['status']=='success') {
        alertify.success(response['msg']);
        this.getAbbrivation()
      } else{
        alertify.error(response['msg']);
      }
    });
  }
 
  
  addBMRChecks(data){
    let temp ={};
    temp['mfr_no'] = this.selectedResult['mfr_no'];
    temp['bmr_checklist'] = data.value;
    this.service.post('bmr_new/ebmr.php?type=saveBMRchecklist',JSON.stringify(temp)).subscribe(response =>{
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
    this.service.post('bmr_new/ebmr.php?type=deletebmrChecklist&mfr_no=' + this.selectedResult['mfr_no'], JSON.stringify(bmr_checklist)).subscribe(response => {
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
    this.service.post('bmr_new/stages.php?type=saveClearance',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.iscleranceAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!")
      }
    });
  }





  saveTable(type){
    let temp = {}


    temp['tables'] = this.tables;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];


    temp['type'] = type;


    this.service.post('bmr_new/stages.php?type=saveTable',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        if(type!='save'){

       
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
  this.getManufacturingStages();
}
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

  
  
  saveLogbook(data,type){
    let temp={};
      // temp['procedures'] = this.procedureList;
      temp['form_no_list'] = this.form_no_list;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveLogbook',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        // this.getManufacturingStages();
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveProcedure(data,type){

    let temp={};
      // temp['procedures'] = this.procedureList;
      temp['procedures'] = this.procedures;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveProcedure',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  SaveisInprocessChecks(data,type){

    let temp={};
     
     

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=SaveisInprocessChecks',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveLineClearance(data,type){

    let temp={};
      // temp['procedures'] = this.procedureList;
      temp['procedures'] = this.LineClearance;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveLineClearance',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveBmrRoom(data,type){
    let temp={};
      // temp['bmrRoomList'] = this.bmrRoomList;
      temp['bmrRoomList'] = this.room;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveBmrRoom',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveBmrRoomAction(data,type){
    let temp={};
      // temp['bmrRoomList'] = this.bmrRoomList;
      temp['roomActions'] = this.roomActions;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
    temp['type'] = type;
  
    this.service.post('bmr_new/stages.php?type=saveBmrRoomAction',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        // this.getManufacturingStages();
        if(type!='save'){
        this.isNewStage = false;
        this.newStageMaster = false;
        this.isNewStage2 = false;
        this.isprocedure='';
  this.isroom='';
  this.isequipment='';
  this.isCleaningChecks='';
  this.isroomActions='';
  this.isEquipmemntCleaning='';
  this.isLogbook='';
  this.istable='';
  this.isFraction='';
  this.isInprocessChecks='';
  this.ismillinsifting='';
  this.isDispensing='';
  this.isMixing='';
  this.isSiftLubrication='';
  this.isDry_SIFTING_MILLING='';
  this.isLineClearance='';
  this.isSieveInteggity='';
  this.isYield='';
  this.isweighing='';
  this.isDrying='';
  this.isBlendLubrication='';
  this.isWeighing_Variation_Recoed='';
  this.isCOMPRESSION_PARAMETERS='';
  this.isINPROCESS_YIELD='';
  this.isYIELD_RECONCILIATION='';
  this.isQcSample='';
  
        this.getManufacturingStages();
        }
        this.isProcedureAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveQALINE(){
    let temp={};
      temp['Qa_LINE'] = this.checklistList;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
  
    this.service.post('bmr_new/stages.php?type=saveQALINE',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }
  saveprodLINE(){
    let temp={};
      temp['prod_LINE'] = this.prod_checklistList;

    temp['stage_id'] = this.selectedStage['id'];
    temp['step_id'] = this.selected_step['id'];
  
    this.service.post('bmr_new/stages.php?type=saveprodLINE',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
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
    this.service.post('bmr_new/stages.php?type=saveScreenCheck',JSON.stringify(temp)).subscribe(response =>{
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
    this.service.post('bmr_new/stages.php?type=saveSieveCheck',JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getManufacturingStages();
        this.isInprocessAdd = false;
        alertify.success("Records Save Successfully");
      }else{
         alertify.error("Error to save Record !!!");
      }
    });
  }




  StagesData;
  ispopup=false;
  popup(index){
    this.ispopup=true;
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    // this.getProcessTypes();
  }




  saveProc(data){
    let temp=data.value;
    temp['product_code']=this.selectedResult['product_code']

    this.service.post('bmr_new/process.php?type=saveMFGProcess',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        // this.selectedResult['instructions'] = response['instructions'];
        this.getManufacturingStages()
        
        alertify.success('SAVE');
        data.resetForm();
       this.ispopup=false;
      } else{
        alertify.error(response['status']);
      }
    });

  }
  stepss=[];
  step

  addsteps(data) {
    if (this.stage==='') {
      alert('Add Stage');
      return;
    }
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
   console.log(this.stage)
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





  for_department='Production';
  stage='';
  saveStagemain(data){  
    
    let temp = data.value;
    temp['stepss']=this.stepss;
    temp['for_deparetmemt']=this.for_department;
    // temp['stage']=this.stage;
    this.processes[this.processes.length] = temp;
    console.log(this.processes);    
    this.stepss=[];
    this.stage='';
}




Stages;
getProcessStage(id){
  
    this.service.get('bmr_new/process.php?type=getProcesses&id='+id).subscribe(response=>{
      this.stagemaster=response;
      this.Stages=this.stagemaster[0]['Stages']
      if (this.results.length > 0) {
        if (this.selectedIndex !== -1) {
          this.selectedResult = this.results[this.selectedIndex];
          this.isStart = true;
          // this.selectStagePage(0)     
          this.selectStagePage(this.MainselectedStageIndex)     
          
          

          console.log('1')
          // this.isView = true;
        } else {
          this.isStart = false;
          // this.isView = false;
          console.log('2')
        }
      } else {
        this.isStart = false;
        this.isView = false;
        console.log('3')
      }
    });

}

isLogbook='';
isAnySalected:boolean=true;
saveStage(data){
  let temp = data.value;

  temp['stage_id'] = this.selectedStage['id'];
  temp['step_id'] = this.selected_step['id'];
  temp['isQcSample'] = this.isQcSample;
   temp['isLineClearance'] = this.isLineClearance;
   temp['isInprocessChecks'] = this.isInprocessChecks;
    temp['ismillinsifting'] = this.ismillinsifting;
      temp['isDispensing'] = this.isDispensing;
    temp['isMixing'] = this.isMixing;
    temp['isSiftLubrication'] = this.isSiftLubrication;
    temp['isDry_SIFTING_MILLING'] = this.isDry_SIFTING_MILLING;
   temp['isSieveInteggity'] = this.isSieveInteggity;
  temp['isYield'] = this.isYield;

  this.service.post('bmr_new/stages.php?type=newStage',JSON.stringify(temp)).subscribe(response =>{
    if(response['status'] == 'success'){
      data.resetForm();
      this.isNewStage = false;
      this.newStageMaster = false;
      this.isNewStage2 = false;
      this.isprocedure='';
this.isroom='';
this.isequipment='';
this.isCleaningChecks='';
this.isroomActions='';
this.isEquipmemntCleaning='';
this.isLogbook='';
this.istable='';
this.isFraction='';
this.isInprocessChecks='';
this.ismillinsifting='';
this.isDispensing='';
this.isMixing='';
this.isSiftLubrication='';
this.isDry_SIFTING_MILLING='';
this.isLineClearance='';
this.isSieveInteggity='';
this.isYield='';
this.isweighing='';
this.isDrying='';
this.isBlendLubrication='';
this.isWeighing_Variation_Recoed='';
this.isCOMPRESSION_PARAMETERS='';
this.isINPROCESS_YIELD='';
this.isYIELD_RECONCILIATION='';
this.isQcSample='';

      this.getManufacturingStages();
      alertify.success("Records Save Successfully");
    }else{
       alertify.error("Error to save Record !!!");
    }
  });
  this.isAnySalected = true;
}


Checking_in='';


completeMaster(status){

 
    let temp ={};
        temp['product_code']=this.selectedResult['product_code']
        temp['status']=status;
    this.service.post('bmr_new/bmr.php?type=complete_bmr_master',JSON.stringify(temp)).subscribe(response =>{
      if(response['status']=='success') {
        this.router.navigate(['..'], { relativeTo: this.route })
      
        alertify.success('SAVE');
      } else{
        alertify.error(response['msg']);
      }
    });
  }
  emp_id;
  idd;
  typeee;
  from;
isDIGI;
  openDigiSign(data,type,from){
    this.emp_id = localStorage.getItem('emp_id');
    console.log(this.emp_id)
   
    this.typeee = type;
    this.from = from;
    this.isDIGI = true;
  }
  loginPassward ='';
  isbutton = true;;
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
        if(this.from=='logbook'){
          this.saveLogbook('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Yield'){
          this.saveYeilds('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Procedures'){
          this.saveProcedure('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='inprocess_checks'){
          this.SaveisInprocessChecks('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Room'){
          this.saveBmrRoom('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Room_Action'){
          this.saveBmrRoomAction('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Equipment_Cleaning_Checks'){
          this.saveCleaningChecks('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='SieveInteggity'){
          this.saveSieveInteggity('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Equipments'){
          this.saveEquipment('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='SIFTING Lubrication'){
          this.saveSIFTING_Lubrication('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='QC_Sample'){
          this.saveQcSample('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Weighing'){
          this.saveWeighings('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Drying'){
          this.saveDrying('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='BlendLubrication'){
          this.saveBlendLubrication('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Mixing'){
          this.saveMixing('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='YIELD RECONCILIATION'){
          this.saveYIELD_RECONCILIATION('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='SIFTING MILLING OF DRY'){
          this.saveDry_SIFTING_MILLING('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='COMPRESSION_PARAMETERS'){
          this.saveCOMPRESSION_PARAMETERS('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Weighing_Variation_Recoed'){
          this.saveWeighing_Variation_Recoed('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='INPROCESS YIELD'){
          this.saveINPROCESSYIELD('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='millinsifting'){
          this.savemillinsifting('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='Dispensing'){
          this.saveDispensing('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='extra_table'){
          this.saveTable(this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
        if(this.from=='LineClearance'){
          this.saveLineClearance('',this.typeee);
          console.log('this.typeee :>> ', this.typeee);
        }
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }

  selectedProcesstype=[];
  selected_Stage:any=[];
  getStages(index){
    this.selectedProcesstype=this.StagesData[index-1];
    this.selected_Stage=this.selectedProcesstype['stages'];
  }
  fg_sub_materials;
  getDosageTypes() {
    this.service.get('master/product.php?type=get_dosage_Form').pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.fg_sub_materials = response;
    });
  }

  products: any[] = [];
  /** Bound to Configure BMR modal: product dropdown selection (fixes template typo LoadPoduct). */
  loadProduct(_index: number): void {
    // Optional: set selected product by index if needed; product_code is already bound via ngModel.
  }

  /** Bound to Configure BMR modal: process type dropdown (fixes template processTypess). */
  processTypess: any = null;

  getProductsByDosage(value: string) {
    this.service.get('master/product.php?type=getProductsByDosageForm&product_type=' + value).pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.products = Array.isArray(response) ? response : [];
    });
    this.getProcessTypes(value);
  }

  getProcessTypes(value: string) {
    this.service.get('bmr/process.php?type=getProcessesmaster&from=BMR&dosage_form=' + value).pipe(takeUntil(this.destroy$)).subscribe(response => {
      this.StagesData = response;
    });
  }
}
