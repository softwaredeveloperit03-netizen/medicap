import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-activity',
  templateUrl: './activity.component.html',
  styleUrls: ['./activity.component.css']
})
export class ActivityComponent implements OnInit {
  isView = false;
  results;
  selectedResult=[];
  selectedIndex = -1;
  isStart = false;
  
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

  employee;
  emp_id = '';
  balances;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getAcceptedRequests();
    this.getLAFEquipments();
    this.getBalances()
  }

  getAcceptedRequests(){
    this.service.get('store/dispensing.php?type=getAcceptedRequests').subscribe(response => {
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.selectedResult = this.results[this.selectedIndex];
        this.isView = true;
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
    this.balance_qty = +this.selectedMaterial['qty'];
    this.isStart = true;

    this.getStoreEmployees();
  }

  getStoreEmployees() {
    this.service.get('store/dispensing.php?type=getStoreEmployees').subscribe(response => {
      this.employees = response;
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

  delData(index){
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
    let temp = {};
    temp['id'] = this.selectedMaterial['id'];
    temp['done_by'] = this.emp_id;
    temp['containers'] = this.containers;
    temp['ars'] = this.ars;
    temp['gross_total']=this.gross_total;
    temp['net_total']=this.net_total;
    temp['tare_total']=this.tare_total;

    this.service.post('store/dispensing.php?type=saveDispensingForm', JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Material Dispensing Completed!');
        this.isStart = false;
        this.getAcceptedRequests();
       this.ars=[];
       this.containers=[];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
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
