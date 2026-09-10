import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-complete',
  templateUrl: './complete.component.html',
  styleUrls: ['./complete.component.css'],
  providers:[DatePipe]
})
export class CompleteComponent implements OnInit {
  operator_type = 'Parmanent Operator';
  area_cleaned_from;
  area_cleaned_to;
  equip_cleaned_from;
  equip_cleaned_to;
  equip_cleaned_by='';
  area_cleaned_by='';
  from_time='';
  to_time='';
  isView = false;
  results;
  selectedResult=[];
  end_laf_time ='';
  selectedIndex = -1;
  // isStart = false;
  today='';
  selectedMaterial = [];
  containers = [];
  ars = [];
  operator;
  // balance_qty = 0;
  employees;
  // gross_wt=0;
  // tare_wt=0;
  lafs;
  // gross_total = 0;
  // tare_total = 0;
  // net_total = 0;

  employee;
  emp_id = '';
  balances;
  qcperson;
  officer;
  qaperson; 
  selectedBalance = [];
  selectedLAF = [];
  start_time = '';
  end_time='';
  start_date: Date;
  end_date:Date;
  pressure_reading = '';
  start_rlaf_time='';
  isViewshow=false;
  selectedview=[];
  selectedContainer=[];
  cleaning_from='';
  cleaning_to='';
  constructor(private service:DataAccessService,private datePipe : DatePipe) {
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd')
   }

  ngOnInit(): void {
    this.getAcceptedRequests();
    // this.getLAFEquipments();
    // this.getBalances()
    this.getOfficer();
    this.getQAPerson();
    this.getStoreEmployees();
     this.getOperators();
  }

  getAcceptedRequests(){
    this.service.get('store/dispensing.php?type=getActiveDispensing').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.selectedResult = this.results[this.selectedIndex];
        this.isView = true;
      } else {
        this.isView = false;
      }
    });
  }

  view(index){
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.isView = true;
  }



  getStoreEmployees() {
    this.service.get('store/dispensing.php?type=getStoreEmployees').subscribe(response => {
      this.employees = response;
    });
  }

  
  getOperators(){
    this.service.get('common.php?type=getOperators').subscribe(response => {
      this.operator = response;
    });
  }

  getOfficer(){
    this.service.get('employee.php?type=getProductionExecutiveOfficers').subscribe(response => {
      this.officer = response;
    });
  }

  getQAPerson(){
    this.service.get('employee.php?type=getQAPersons').subscribe(response => {
      this.qaperson = response;
    });
  }

  getBalances() {
    this.service.get('balance.php?type=getSamplingBalances').subscribe(response => {
      this.balances = response;
    });
  }


  getLAFEquipments() {
    this.service.get('equipments.php?type=getLAFEquipments').subscribe(response=> {
      this.lafs = response;
    });
  }

  selectLAF(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedLAF = this.lafs[index];
    } else {
      this.selectedLAF = [];
    }
  }

  getCurrentTime() {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.start_time = h + ':' + m;
    this.start_date = new Date();
  }

 
  getEndTime() {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.end_time = h + ':' + m;
     this.end_date = new Date();
  }

  getCurrentRLAFTime(){
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.start_rlaf_time = h + ':' + m;
    this.start_date = new Date();
  }


  laf_stop_time(){
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.end_laf_time = h + ':' + m;
    this.end_date = new Date();
  }

  getCurrentCleanTime(){
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.cleaning_from = h + ':' + m;
    this.start_date = new Date();
  }

  getEndCleanTime(){
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.cleaning_to = h + ':' + m;
    this.end_date = new Date();
  }

 
  viewshow(index){
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];
    this.selectedContainer=this.selectedMaterial['containers'];
    console.log(this.selectedContainer);
    this.isViewshow=true;
  }



  saveDispensingActivity(data) {
    if (!data.valid) {
      alertify.error('All Feilds are required!');
      return;
    }
    let temp=data.value;
    temp['id']=this.selectedResult['id'];
    
    this.service.post('store/dispensing.php?type=saveDispensingActivity', JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save Successfuly');
        this.isView = false;
        this.selectedIndex = -1;
        this.getAcceptedRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



}