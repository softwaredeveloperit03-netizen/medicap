import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;

  from_date = '';
  to_date = '';
  max_date = '';
  units; 
  company_unit='';
  product_type = '';
  isView=false;
  selectedResult=[];
  gross_wt=0;
  tare_wt=0;
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  sample_qty=0;
  containers=[];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getCompletedBatches();
    this.getUnits();
  }

  getUnits() {
    this.service.get('common.php?type=getCompanyUnits').subscribe(response => {
      this.units = response;
    });
  } 

  getCompletedBatches() {
    this.service.get('production/lot/sampling.php?type=getPendingBatches').subscribe(response => {
      this.results = response;
    });
  }


  view(index){
    this.selectedResult=this.results[index];
    this.sample_qty = +this.selectedResult['sample_qty'];
    this.isView=true;
  }

  adddata(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    if (+this.sample_qty > 0) {
      temp['net_wt'] = this.gross_wt - this.tare_wt;
      let qty = +parseFloat((+this.sample_qty - +temp['net_wt']) + '').toFixed(2);
      if (qty >= 0) {
        this.containers[this.containers.length] = temp;
        this.sample_qty = +parseFloat((+this.sample_qty - +temp['net_wt']) + '').toFixed(2);
      } else {
        alertify.error('Sample Qty1:' + this.sample_qty);
      }
    } else {
      alertify.error('Sample Qty2:' + this.sample_qty);
    }

    this.gross_total = 0;
    this.tare_total = 0;
    this.net_total = 0;
    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];
      this.gross_total = this.gross_total + +container['gross_wt'];
      this.tare_total = this.tare_total + +container['tare_wt'];
      this.net_total = this.net_total + +container['net_wt'];

      this.gross_total = +parseFloat(this.gross_total + '').toFixed(2);
      this.tare_total = +parseFloat(this.tare_total + '').toFixed(2);
      this.net_total = +parseFloat(this.net_total + '').toFixed(2);
    }

    data.reset();
    this.gross_wt = 0;
    this.tare_wt = 0;
  }

  delData(index){
    this.containers.splice(index, 1);
  }

  send(){
    let temp={};
    temp['product_code']=this.selectedResult['product_code'];
    temp['batch_no']=this.selectedResult['batch_no'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['batch_qty']=this.selectedResult['yield_qty'];
    temp['mfg_date']=this.selectedResult['complete_date'];
    temp['exp_date']='';
    temp['retest_date']='';
    temp['lmr_no']=this.selectedResult['lmr_no'];
    temp['company_unit']=this.selectedResult['company_unit'];
    temp['start_date']=this.selectedResult['start_date'];
    temp['complete_date']=this.selectedResult['complete_date'];
    temp['gross_total']=this.gross_total;
    temp['tare_total']=this.tare_total;
    temp['net_total']=this.net_total;
    temp['containers']=this.containers;
    this.service.post('production/lot/sampling.php?type=sendIntimation',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('send Intimation Successfuly');
        this.getCompletedBatches();
        this.isView=false;
      }else{
        alertify.error('Some error Ocuured!');
      }

    });
  }

}
