import { Component, OnInit } from '@angular/core';
import {DataAccessService} from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-pressure1',
  templateUrl: './pressure1.component.html',
  styleUrls: ['./pressure1.component.css'],
  providers:[DatePipe]
})
export class Pressure1Component implements OnInit {
companyUnits;
from_date='';
to_date='';
today = '';
entry_date = '';
entry_time = '';
equipment_code = '';
ahus;
results;
  constructor(private service :DataAccessService,private datePipe : DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.resetEntryDefaults();
   }

  ngOnInit(): void {
    this.service.observablePlant.subscribe(response =>{
      this.companyUnits = response;
    });
    this.getAHU();
    this.getPressure1();
  }

  resetEntryDefaults() {
    this.entry_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.entry_time = this.datePipe.transform(Date.now(), 'HH:mm');
    this.equipment_code = '';
  }

  getAHU(){
    this.service.get('equipments.php?type=getAHU').subscribe(response =>{
        this.ahus= response;
    });
  }
  getPressure1(){
    this.service.get('engineering/ahu.php?type=getPressure1&from_date='+this.from_date+'&to_date='+this.to_date).subscribe( response =>{
      this.results =response;
    });
  }
  download(){
    this.service.open('engineering/ahu.php?type=downloadPressure1&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  savePressure1(data){
    if(!data.valid){
      alertify.error("All Fields are required !!");
      return;
    }
    const payload = {
      entry_date: this.entry_date,
      entry_time: this.entry_time,
      equipment_code: this.equipment_code || data.value.equipment_code,
      primary_filter: data.value.primary_filter,
      secondary_filter: data.value.secondary_filter,
      hepa_filter: data.value.hepa_filter,
      remark: data.value.remark
    };
    this.service.post('engineering/ahu.php?type=savePressure1',JSON.stringify(payload)).subscribe(response =>{
      if(response['status']=='success'){
        const keepFrom = this.from_date;
        const keepTo = this.to_date;
        this.getPressure1();
        data.resetForm();
        this.from_date = keepFrom;
        this.to_date = keepTo;
        this.resetEntryDefaults();
        alertify.success("Records Save Successfully");
      }
      else{
        alertify.error("Error to Save Records !!");
      }
    });
  }
}
