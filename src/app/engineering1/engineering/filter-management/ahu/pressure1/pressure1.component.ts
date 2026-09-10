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
ahus;
results;
  constructor(private service :DataAccessService,private datePipe : DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.service.observablePlant.subscribe(response =>{
      this.companyUnits = response;
    });
    this.getAHU();
    this.getPressure1();
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
    this.service.post('engineering/ahu.php?type=savePressure1',JSON.stringify(data.value)).subscribe(response =>{
      if(response['status']=='success'){
        this.getPressure1();
        data.resetForm();
        alertify.success("Records Save Successfully");
      }
      else{
        alertify.error("Error to Save Records !!");
      }
    });
  }
  
}
