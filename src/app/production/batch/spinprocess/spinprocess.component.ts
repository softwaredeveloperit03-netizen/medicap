import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-spinprocess',
  templateUrl: './spinprocess.component.html',
  styleUrls: ['./spinprocess.component.css']
})
export class SpinprocessComponent implements OnInit {


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
  operator_names;
  selectedPage = 0;
  constructor(private service: DataAccessService) {
    // this.expected_start_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    // this.expected_complete_date = this.datePipe.transform(Date.now(),'yyyy-MM-01'); 
    
   }

  ngOnInit() {
    this.getReadyBatchPlans();
    this.getEmployees();
    this.getOperators();
   
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
  siftings;
  get_sifting(){
    this.service.get('production/product.php?type=get_sifting&id='+this.selectedResult['id']).subscribe(response=>{
      this.siftings = response;
     
     
    });
  }
  oprpccp;
  getoprp(){
    this.service.get('production/product.php?type=get_saveoprp2&id='+this.selectedResult['id']).subscribe(response=>{
      this.oprpccp = response;
     
     
    });
  }
  oprps;
  oprp_room_details:any;
  oprp_room:any;
  getCCP(){
    this.service.get('production/product.php?type=get_saveoprp1&id='+this.selectedResult['id']).subscribe(response=>{
      this.oprps = response;
      this.oprp_room = this.oprps;
       this.oprp_room_details = this.oprp_room.oprp_room_details;
      console.log(this.oprp_room);
      console.log(this.oprp_room_details);
    });
  }

  getReadyBatchPlans() {
    this.service.get('production/product.php?type=getunder_prod_BatchPlans_sp&material_type=Raw Material').subscribe(response => {
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
  bmr_stages: any = [];
  bmr_all_stages() {
    this.service.get('production/product.php?type=bmr_all_stages&id=' + this.selectedResult['id']).subscribe(response => {
      this.bmr_stages = response;
    });
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

  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getoprp();
    this.getCCP();
    this.getbmr_blending();  
    this.bmr_all_stages();  
    this.getbmr_sifting();  
    this.get_sifting();
    console.log(this.bmr_stages);
  }

  isNewSupervisors(id) {
    this.id = id;
    this.newSupervisor = true;
  }

  isNewWorkers(id) {
    this.id = id;
    this.newWorker = true;
  }
  qc_stat(id){
    // this.service.get('production/product.php?type=update_qc_stat&id='+id).subscribe(response=>{
    //   this.bmr_blenders = response;
    // });
    // this.getbmr_sifting();
    this.service.post('production/product.php?type=update_qc_stat&id='+id, JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sent For Sampling To IPQC');
        this.getbmr_sifting();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  startBatch() {
    this.service.post('production/product.php?type=send_qc_int&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Start Production Successfully');
        this.isView = false;
        this.getReadyBatchPlans();
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
