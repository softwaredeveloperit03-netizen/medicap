import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import {DatePipe} from '@angular/common';

@Component({
  selector: 'app-ahu',
  templateUrl: './ahu.component.html',
  styleUrls: ['./ahu.component.css'],
  providers:[DatePipe]
})
export class AhuComponent implements OnInit {

  results;
  isStart=false;
  selectedStart=[];
  isStop=false;
  selectedStop=[];
  isPressureDiff=false;
  start_pressure ='';
  stop_time='';
  today='';
  to_date='';
  from_date='';
  start_time='';
  isTime =false;
  corridor = '';
  sampling = '';
  dispensing = '';
  current_time='';
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
   this.current_time=this.datePipe.transform(Date.now(),'hh:mm:ss'); 
   this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getAHURecords();
  }

  getAHURecords(){
    this.service.get('store/dispensing.php?type=getAHURecords&from_date='+this.from_date +'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  start(index){
    this.selectedStart=this.results[index];
    this.isStart=true;
  }

  stop(index){
    this.selectedStop=this.results[index];
    this.isStop=true;
  }

  pressureReading(index){
    this.selectedStart=this.results[index];
    this.isPressureDiff=true;
  }

  startAHU(){
    this.current_time=this.datePipe.transform(Date.now(),'hh:mm:ss'); 
    let temp = this.selectedStart;
    temp['start_time'] = this.start_time;/* 
    temp['corridor'] = this.corridor;
    temp['sampling'] = this.sampling;
    temp['dispensing'] = this.dispensing; */
    this.service.post('store/dispensing.php?type=startAHU', JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save Successfuly');
        this.isStart = false;
        this.getAHURecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

    savePressure(){
    let temp = this.selectedStart;
    temp['corridor'] = this.corridor;
    temp['sampling'] = this.sampling;
    temp['dispensing'] = this.dispensing;

    this.service.post('store/dispensing.php?type=saveAHUPressure', JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save Successfuly');
        this.isPressureDiff = false;
        this.getAHURecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


  stopAHU(){
    this.service.get('store/dispensing.php?type=stopAHU&id='+this.selectedStop['id']+'&stop_time=' +this.stop_time).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save Successfuly');
        this.isStop = false;
        this.getAHURecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  downloadLog(){
    this.service.open('store/dispensing.php?type=downloadAHURecords&from_date='+this.from_date +'&to_date='+this.to_date);
  }

}