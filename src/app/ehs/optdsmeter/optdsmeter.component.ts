import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-optdsmeter',
  templateUrl: './optdsmeter.component.html',
  styleUrls: ['./optdsmeter.component.css'],
  providers: [DatePipe]
})
export class OptdsmeterComponent implements OnInit {

  optTDSMeter;
  date;
  isNew = false;
  selectedtdsMeter = [];

  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe
  ) {
    this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getTDSMeterRecord();
    
  }

  optdsmeter
  getTDSMeterRecord(){
    this.service.get('common.php/type?=getTDSMeter').subscribe(response => {
      this.optdsmeter = response
    });
  }
  
  // getTDSMeterPurpose(){

  // }

  saveTdsMeter(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/tdsmeter.php?type=saveTDSMeter',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success')
      {
        alertify.success('Record Saved Successfully');
        this.isNew = false;
        this.getTDSMeterRecord();
      }
      else{
        alertify.error(response['status']);
      }
    });
  }

  updateStatus(){
    
  }
  
}
