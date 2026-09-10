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
//  containers = [];
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
  end_laf_time ='';
  isViewshow=false;
  selectedview=[];
  selectedContainer=[];
  operator;
  cleaning_from='';
  cleaning_to='';
  operators;
  operator_name ='';
  constructor(private service:DataAccessService,private datePipe : DatePipe) {
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd')
   }

  ngOnInit(): void {
    this.getAcceptedRequests();
    this.getOperators();
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

  getOperators() {
    this.service.get('common.php?type=getLabours').subscribe(response => {
      this.operators = response;
    });
  }

  show(index) {
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];
    this.balance_qty = +this.selectedMaterial['qty'];
    this.ars = this.selectedMaterial['available_ars'];


    let require_qty = this.selectedMaterial['qty'];
    let added_qty = 0;
    
    this.gross_total = 0;
    this.tare_total = 0;
    this.net_total = 0;

    let containers = [];
    let ars = [];
    for(let i=0; i < this.ars.length;i++) {
      let arss = this.ars[i];

      let qty = +arss['balance_qty'];
      
      while (qty !== 0 && require_qty !== 0) {
        if (require_qty > 0 && qty > 0) {
          let container_qty = +arss['pack_size'];
          if (require_qty >= +arss['pack_size']) {
            let container = {};
            container['container_no'] = containers.length;
            container['ar_no'] = arss['ar_no'];
            container['gross_wt'] = +arss['pack_size'];
            container['tare_wt'] = 0;
            container['net_wt'] = +arss['pack_size'];
            containers[containers.length] = container;
            qty = qty - +arss['pack_size'];
            added_qty += +arss['pack_size'];
            require_qty -= +arss['pack_size'];
          } else if (+arss['pack_size'] >= require_qty) {
            let container = {};
            container['container_no'] = containers.length;
            container['ar_no'] = arss['ar_no'];
            container['gross_wt'] = require_qty;
            container['tare_wt'] = 0;
            container['net_wt'] = require_qty;
            containers[containers.length] = container;
            qty = qty - require_qty;
            added_qty += require_qty;
            require_qty -= require_qty;
          } else {
            break;
          }
        }
      }
      let temp = {};
      temp['ar_no'] = arss['ar_no'];
      temp['qty'] = +arss['balance_qty'] - qty;
      ars[ars.length] = temp;
      if (require_qty == 0) {
        break;
      }
    }
    if (+this.selectedMaterial['qty'] == added_qty) {
      this.selectedMaterial['containers'] = containers;
      this.selectedMaterial['ars'] = ars;
      this.selectedMaterial['qty_availablity'] = 'YES';
    } else {
      this.selectedMaterial['qty_availablity'] = 'NO';
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
    if (!data.valid) {
      alertify.error('All fields are required!');
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
    this.selectedMaterial['product_code'] = this.selectedMaterial['product_code'];
    this.selectedMaterial['operator_name'] = this.operator_name;
    this.selectedMaterial['prod_batch_no'] = this.selectedMaterial['batch_no'];
    this.service.post('store/dispensing.php?type=saveDispensingForm&dispensing_no='+ this.selectedMaterial['dispensing_no'], JSON.stringify(this.selectedMaterial)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Material Dispensing Completed!');
        this.isStart = false;
        this.getAcceptedRequests();
       this.ars=[];
       this.containers=[];
      this.selectedMaterial=[];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



 
}
