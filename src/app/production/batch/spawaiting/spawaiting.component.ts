import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-spawaiting',
  templateUrl: './spawaiting.component.html',
  styleUrls: ['./spawaiting.component.css'],
  providers: [DatePipe]
})
export class SpawaitingComponent implements OnInit {
  temp=0;
  humidity=0;
  results;
  isView = false;
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
  clicked = false;
  operator_names;
  selectedPage = 0;
  from_date;
  to_date;
  // router: any;
  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
    // this.expected_start_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    // this.expected_complete_date = this.datePipe.transform(Date.now(),'yyyy-MM-01'); 
    
   

  ngOnInit() {
    this.getReadyBatchPlans();
    this.getEmployees();
    this.getOperators();
    this.getoprp();
    this.getCCP();
    this.getblender();  
    // this.getbmr_sifting();  
    this.getemp();  
    this.getemp1();  
    this.getemp2();  
    this.GET_SAVEgEN_INSTRUCTION();  
    this.getEquipmentCleaningLog();  
    this.getsifterr();  
    this.GET_line_chek_process();  

  }
  remarkvalue: string[] = []; // Initialize it as an empty array or with your data
  // sifters;
  // getsifterr(){
  //   this.service.get('bmr/process.php?type=getsifterr').subscribe(response=>{
  //     this.sifters = response;
  //   });
  // }


  // chk_poins=[];
  // add_chk_poins(data) {
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.chk_poins[this.chk_poins.length] = temp;
   
  //   data.resetForm();
  // }
  chk_poins;
    GET_line_chek_process(){
    this.service.get('bmr/process.php?type=GET_line_chek_process').subscribe(response =>{
      this.chk_poins=response;
    });
  }
  // bmr_sifter=[];
  // add_savesift(data) {
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   this.bmr_sifter[this.bmr_sifter.length] = temp;
  //   console.log(this.bmr_sifter);
   
  //   data.resetForm();
  // }
  // bmr_blenders=[];
  add_saveblend(data) {

  if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    this.service.post('production/product.php?type=saveblend&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  // delData(index) {
  //   this.bmr_sifter.splice(index, 1);
  // }
  sifting=[];
  add_sifting(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.sifting[this.sifting.length] = temp;
   
    data.resetForm();
  }
  deletesifting(index){
    this.sifting.splice(index);
  }
  equips;
  getEquipmentCleaningLog(){
    this.service.get('equipments.php?type=getEquipmentCleaningLog_today&from_date='+this.from_date+'&to_date='+this.to_date ).subscribe(response=>{
      this.equips=response;
    });
  }
  instructions;
  GET_SAVEgEN_INSTRUCTION(){
    this.service.get('bmr/process.php?type=GET_SAVEgEN_INSTRUCTION').subscribe(response =>{
      this.instructions =response;
    });
  }
  selectPage(index, index1) {
    this.selectedPage = index;
  
     
  }
  bmr_sifter;
  getbmr_sifting(){
    this.service.get('production/product.php?type=get_savebmr_sift&id='+this.selectedResult['id']).subscribe(response=>{
      this.bmr_sifter = response;
     
     
    });
  }
  oprpccp;
  getoprp(){
    this.service.get('bmr/process.php?type=get_saveoprpccp').subscribe(response=>{
      this.oprpccp = response;
     
     
    });
  }
  oprps;
  oprp_room_details:any;
  oprp_room:any;
  getCCP(){
    this.service.get('bmr/process.php?type=GET_oprp_chek').subscribe(response=>{
      this.oprps = response;
      this.oprp_room = this.oprps;
       this.oprp_room_details = this.oprp_room.oprp_room_details;
      console.log(this.oprp_room);
      console.log(this.oprp_room_details);
    });
  }

  getReadyBatchPlans() {
    this.service.get('production/product.php?type=getReadyBatchPlans_sp&material_type=Raw Material').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.view(this.selectedIndex);
      }
    });
  }

  bmr_blenders;
  getbmr_blending(){
    this.service.get('production/product.php?type=get_savebmr_blend&id='+this.selectedResult['id']).subscribe(response=>{
      this.bmr_blenders = response;
    });
  }
  del_blend(id){
    this.service.get('production/product.php?type=del_savebmr_blend&id='+id).subscribe(response=>{
      this.bmr_blenders = response;
    });
    this.getbmr_blending();
  }
  delData(id){
    this.service.get('production/product.php?type=del_savebmr_sift&id='+id).subscribe(response=>{
      this.bmr_sifter = response;
    });
    this.getbmr_sifting();
  }

  // save_mchk_poinsForm(data){
    
  //   if (!data.value) {
  //     alert('All fields are required');
  //     return;
  //   }

  //   let temp = data.value;
  // temp['chk_poins'] = this.chk_poins;
  //   // let temp = data.value;
  //   // temp['chk_poins'] = this.chk_poins;
  //   this.service.post('production/product.php?type=save_lc_procc_cheklist2323&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alert('Saved Successfully');
  //       this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
  //       this.getReadyBatchPlans();
  //        this.isView = false;
  //       data.reset();
        
  //       } else {
  //       alert('Failed: An error occured, please try again!');
  //     }
  //   });
  // }
  save_mchk_poinsForm(data) {
    if (!data.value) {
      alert('All fields are required');
      return;
    }
  
    let temp = data.value;
    temp.chk_poins = this.chk_poins; // Include chk_poins array in the data
  
    this.service.post('production/product.php?type=save_lc_procc_cheklist2323&id=' + this.selectedResult['id'], JSON.stringify(temp))
      .subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
          this.getReadyBatchPlans();
          this.isView = false;
          data.reset();
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }
  
  save_sifting(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['sifting'] = this.sifting;
    this.service.post('production/product.php?type=save_sifitng&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveoprpccp(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['oprps'] = this.oprps;
    this.service.post('production/product.php?type=saveoprp1&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveoprpccp2(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['oprps2'] = this.oprpccp;
    this.service.post('production/product.php?type=saveoprp2&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  saveblend(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
  
    this.service.post('production/product.php?type=saveblendmfg&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  batch_number;
  save_sifting_form(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    
    // temp['bmr_sifter'] = this.bmr_sifter;
    this.service.post('production/product.php?type=save_sifting&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        // this.bmr_sifter=[];
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  save_stage(data){
 
    let temp = data.value;
    temp['Stage_List'] = this.Stage_List;
    this.service.post('production/product.php?type=save_stage&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  bmr_stages: any = [];
  bmr_all_stages() {
    this.service.get('production/product.php?type=bmr_all_stages&id=' + this.selectedResult['id']).subscribe(response => {
      this.bmr_stages = response;
    });
  }
  Maddgen(data){
 
    let temp = data.value;
    temp['instructions'] = this.instructions;
    this.service.post('production/product.php?type=save_bmr_instruction&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  save_eqip(data){
 
    let temp = data.value;
    temp['equips'] = this.equips;
    this.service.post('production/product.php?type=save_bmr_sp_equpments_all_data&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
        this.getReadyBatchPlans();
         this.isView = false;
        data.reset();
        
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  Stage_List=[];
  Add_stage(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
   
    temp['batch_number'] = this.selectedResult['batch_number'];
    // temp['emp_name']=this.selected_emp['firstname'];
    // temp['emp_name1']=this.selected_emp1['firstname'];
    // temp['emp_name2']=this.selected_emp2['firstname'];
    // temp['emp_id']=this.selected_emp['emp_id'];
    // temp['this.emp1'] = this.selectedResult['batch_number'];
    this.Stage_List[this.Stage_List.length] = temp;
    console.log(this.Stage_List);
    data.resetForm();
  }
  employees;
  getemp(){
    this.service.get('equipments.php?type=getLabours&department1=Production&operator_category=Worker / Operator').subscribe(response=>{
      this.employees=response;
    })
  }
  employees1;
  getemp1(){
    this.service.get('equipments.php?type=getLabours&department1=Production&operator_category=Staff').subscribe(response=>{
      this.employees1=response;
    })
  }
  employees2;
  getemp2(){
    this.service.get('equipments.php?type=getLabours&department1=Production&operator_category=Staff').subscribe(response=>{
      this.employees2=response;
    })
  }

  sifters;
  getsifterr(){
    this.service.get('bmr/process.php?type=getsifterr').subscribe(response=>{
      this.sifters = response;
    });
  }

  blenders;
  getblender(){
    this.service.get('bmr/process.php?type=getblender').subscribe(response=>{
      this.blenders = response;
    });
  }











  selected_emp;
  emp1;
  getEmployees_id(index){
this.selected_emp=this.employees[index-1];
this.emp1=this.selected_emp['firstname'];
console.log(this.selected_emp)
console.log(this.emp1)
}
selected_emp1
  getEmployees_id2(index){
this.selected_emp1=this.employees1[index-1];
this.emp1=this.selected_emp['firstname'];
console.log(this.selected_emp)
console.log(this.emp1)
}
selected_emp2
  getEmployees_id3(index){
this.selected_emp2=this.employees1[index-1];
this.emp1=this.selected_emp['firstname'];
console.log(this.selected_emp)
console.log(this.emp1)
}







  // /////////////////////////////////////////////////////////////////////////////////////////////////////////

  // /////////////////////////////////////////////////////////////////////////////////////////////////////////

  // /////////////////////////////////////////////////////////////////////////////////////////////////////////

  // /////////////////////////////////////////////////////////////////////////////////////////////////////////
  getEmployees() {
    this.service.get('employee.php?type=getProductionExecutiveOfficers').subscribe(response => {
      this.supervisors = response;
    });
  }

  getOperators() {
    this.service.get('common.php?type=getLabours').subscribe(response => {
      this.operators = response;
    });
  }
  mfg_date='';
exp_date;
  label_claim;
  No_Of_Lots;
  mfg_dates;
  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];    
    this.mfg_date=this.selectedResult['mfg_date'];
    this.mfg_dates=this.selectedResult['mfg_date'];
    this.exp_date=this.selectedResult['exp_date'];
    this.No_Of_Lots=this.selectedResult['no_of_lots'] 
    this.label_claim=JSON.parse(this.selectedResult['label_claim']);
    this.isView = true;
    this.getbmr_blending();
    this.getbmr_sifting();
    this.bmr_all_stages();  

    console.log(this.mfg_date)
  }

  isNewSupervisors(id) {
    this.id = id;
    this.newSupervisor = true;
  }

  isNewWorkers(id) {
    this.id = id;
    this.newWorker = true;
  }

  startBatch() {
    this.service.post('production/product.php?type=startBatch_sp&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Start Production Successfully');
    
        this.router.navigate(['/prod-f-ebmr/batch/spawaiting']);
      } else {
        alertify.error(response['status']);
      }
    });
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
 
}
