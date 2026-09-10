import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-withdraw',
  templateUrl: './withdraw.component.html',
  styleUrls: ['./withdraw.component.css']
})
export class WithdrawComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  total = 0;
  additional_sample=0;
  stability_sample;
  units: any = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
    this.getPendingWithdrawals();
  }

  getPendingWithdrawals(){
    this.service.get('ipqc/finish.php?type=getPendingWithdrawals').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  
  }
  fg_sample_qty=0;
  calculation(){
    this.total = +this.selectedResult['chemical_analysis'] + +this.selectedResult['microbiology_analysis'] + +this.selectedResult['reserve_analysis'] + +this.selectedResult['additional_sample']+ +this.fg_sample_qty;
  }

  withdrawSample(){
    let temp={};
    temp['chemical_analysis']=this.selectedResult['chemical_analysis'];
    temp['microbiology_analysis']=this.selectedResult['microbiology_analysis'];
    temp['reserve_analysis']=this.selectedResult['reserve_analysis'];
    temp['additional_sample']= this.selectedResult['additional_sample'];
    temp['total_qty']= this.total;
    temp['sampling_unit']= this.selectedResult['sampling_unit'];;
    temp['id']= this.selectedResult['id'];
    this.service.post('ipqc/finish.php?type=withdrawSample',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data successfuly withdraw');
        this.getPendingWithdrawals();
        this.isView=false;
      }else{
        alertify.error('Some error occured!');
      }
    });
  }
}
