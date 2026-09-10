import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css'],
  providers:[DatePipe]
})
export class InprocessComponent implements OnInit {

  isView = false;
  results;
  selectedResult=[];
  selectedIndex = -1;
  isStart = false;
  today='';
  selectedMaterial = [];
  containers = [];
  ars = [];
  balance_qty = 0;
  employees;
  gross_wt=0;
  tare_wt=0;
  lafs;
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  dispensing_room;
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
  rlaf_start;
  end_laf_time ='';
  isViewshow=false;
  selectedview=[];
  selectedContainer=[];
  operator;
  cleaning_from='';
  cleaning_to='';
  constructor(private service:DataAccessService,private datePipe : DatePipe) {
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd')
   }

  ngOnInit(): void {
    this.getAcceptedRequests();
  }

  getAcceptedRequests(){
    this.service.get('store/dispensing.php?type=getStartedDispensing').subscribe(response => {
      this.results = response;
      if (this.results.length > 0) {
        if (this.selectedIndex !== -1) {
          this.selectedResult = this.results[this.selectedIndex];
          this.isView = true;
        } else {
          this.isStart = false;
          this.isView = false;
        }
      } else {
        this.isStart = false;
        this.isView = false;
      }
    });
  }

  view(index){
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  show(index) {
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];

    if (this.selectedMaterial['material_code'] == 'RM-017') {
      this.balance_qty = 326;
    } else {
      this.balance_qty = +this.selectedMaterial['qty'];
    }
    this.isStart = true;

    this.getStoreEmployees();
  }

  getStoreEmployees() {
    this.service.get('store/dispensing.php?type=getStoreEmployees').subscribe(response => {
      this.employees = response;
    });
  }

  adddata(data) {
    if(!data.valid){
      alertify.error("all fields are required");
      return;
    }
    let temp = data.value;
    if (+this.balance_qty > 0) {
      temp['net_wt'] = this.gross_wt - this.tare_wt;
      let qty = +parseFloat((+this.balance_qty - +temp['net_wt']) + '').toFixed(2);
      if (qty >= 0) {
        this.containers[this.containers.length] = temp;
        this.balance_qty = +parseFloat((+this.balance_qty - +temp['net_wt']) + '').toFixed(2);
      } else {
        alertify.error('Balance Qty1:' + this.balance_qty);
      }
    } else {
      alertify.error('Balance Qty2:' + this.balance_qty);
    }

    this.ars = [];

    this.gross_total = 0;
    this.tare_total = 0;
    this.net_total = 0;
    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];
      this.gross_total = this.gross_total + +container['gross_wt'];
      this.tare_total = this.tare_total + +container['tare_wt'];
      this.net_total = this.net_total + +container['net_wt'];

      let flag = 0;
      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          flag = 1;
        }
      }
      if (flag == 0) {
        let temp = {};
        temp['ar_no'] = container['ar_no'];
        temp['qty'] = 0;
        this.ars[this.ars.length] = temp;
      }

      this.gross_total = +parseFloat(this.gross_total + '').toFixed(2);
      this.tare_total = +parseFloat(this.tare_total + '').toFixed(2);
      this.net_total = +parseFloat(this.net_total + '').toFixed(2);
    }

    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];

      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          ar['qty'] = +ar['qty'] + +container['net_wt'];
        }
        this.ars[j] = ar;
      }
    }
    data.reset();
    this.gross_wt = 0;
    this.tare_wt = 0;
  }

  viewshow(index){
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];
    this.selectedContainer=this.selectedMaterial['containers'];
    console.log(this.selectedContainer);
    this.isViewshow=true;
  }

  delData(index){
    let container = this.containers[index];
    this.gross_total = this.gross_total - +container['gross_wt'];
    this.tare_total = this.tare_total - +container['tare_wt'];
    this.net_total = this.net_total - +container['net_wt'];

    this.balance_qty = +parseFloat((+this.balance_qty  + +container['net_wt']) + '').toFixed(2);

    this.containers.splice(index, 1);

    this.ars = [];
    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];
      this.gross_total = +container['gross_wt'];
      this.tare_total = +container['tare_wt'];
      this.net_total = +container['net_wt'];

      let flag = 0;
      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          flag = 1;
        }
      }
      if (flag == 0) {
        let temp = {};
        temp['ar_no'] = container['ar_no'];
        temp['qty'] = 0;
        this.ars[this.ars.length] = temp;
      }
    }

    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];

      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          ar['qty'] = +ar['qty'] + +container['net_wt'];
        }
        this.ars[j] = ar;
      }
    }
  }
  saveDispensingForm() {
    if (this.balance_qty != 0) {
      alertify.error('Balance Qty:' + this.balance_qty);
      return;
    }
    if(this.emp_id == '') {
      alertify.error('Plese Select Done By');
      return;
    }
    let temp = {};
    temp['id'] = this.selectedMaterial['id'];
    temp['product_code'] = this.selectedResult['product_code'];
    temp['prod_batch_code'] = this.selectedResult['batch_no'];
    temp['done_by'] = this.emp_id;
    temp['containers'] = this.containers;
    temp['ars'] = this.ars;
    temp['gross_total']=this.gross_total;
    temp['net_total']=this.net_total;
    temp['tare_total']=this.tare_total;
    temp['material_type']=this.selectedMaterial['material_type'];
    temp['material_subtype']=this.selectedMaterial['material_subtype'];
    temp['material_code']=this.selectedMaterial['material_code'];
    temp['unit']=this.selectedMaterial['unit'];
    temp['ar_no']=this.selectedMaterial['ar_no'];
    temp['batch_no']=this.selectedMaterial['batch_no'];
    temp['dispensing_room'] = this.dispensing_room;
    temp['rlaf_start'] = this.rlaf_start
    temp['pressure_reading'] = this.pressure_reading;
    this.service.post('store/dispensing.php?type=saveDispensingForm&dispensing_no='+ this.selectedMaterial['dispensing_no'], JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Material Dispensing Completed!');
        this.isStart = false;
        this.getAcceptedRequests();
       this.ars=[];
       this.containers=[];
      this.selectedMaterial=[];
      temp=[];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  print(data) {
    this.service.open('store/dispensing.php?type=printLabel&material_code='+data+'&id='+this.selectedResult['id']);
  }


 
}
