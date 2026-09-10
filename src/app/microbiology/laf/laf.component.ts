import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-laf',
  templateUrl: './laf.component.html',
  styleUrls: ['./laf.component.css'],
  providers: [DatePipe]
})
export class LafComponent implements OnInit {

  results;
  from_date='';
  to_date='';
  today='';
  selectedResult=[];
  details;
  pressure;
  activity_start;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getActivities();
  }

  getActivities(){
    this.service.get('microbiology/laf.php?type=getActivities&from_date=' +this.from_date +'&to_date=' +this.to_date).subscribe(response =>{
      this.results = response;

      for (let  i = 0; i < this.results.length; i++) {
        let result = this.results[i];
        if (result['status'] == 'BEFORE ACTIVITY UV ON') {
          let on_time = result['before_uv_on'];
          on_time = on_time.split(":");
          let hour = +on_time[0] + 1;
          let date = new Date();
          let time1 = new Date(date.getFullYear(), date.getMonth(), date.getDate(), hour, on_time[1]);
          if (time1 <= new Date()) {
            result['isStop'] = 'YES';
          } else {
            result['isStop'] = 'NO';
          }
          this.results[i] = result;
        } else if (result['status'] == 'AFTER ACTIVITY UV ON') {
          let on_time = result['after_uv_on'];
          on_time = on_time.split(":");
          let min = +on_time[1] + 15;
          let date = new Date();
          let time1 = new Date(date.getFullYear(), date.getMonth(), date.getDate(), on_time[0],min);
          if (time1 <= new Date()) {
            result['isUVStop'] = 'YES';
          } else {
            result['isUVStop'] = 'NO';
          }
          this.results[i] = result;
          console.log(this.results[i])
        }
      }
    });
  }
  
  stop(index){
    this.selectedResult = this.results[index];
    this.service.get('microbiology/laf.php?type=stopUVLight&id='+this.selectedResult['id']).subscribe(response =>{
      if(response['status']=='success'){
        this.getActivities();
        alertify.success("On Time Stop !!");
      } else{
        alertify.error("Error To Stop !!")
      }
    });
  }

  start(index){
    this.selectedResult = this.results[index];
    this.service.get('microbiology/laf.php?type=startUVLight&id='+this.selectedResult['id']).subscribe(response =>{
      if(response['status']=='success'){
        this.getActivities();
        alertify.success("On Time Starts !!");
      } else{
        alertify.error("Error To Start !!")
      }
    });
  }

  end(index){
    this.selectedResult = this.results[index];
    this.service.get('microbiology/laf.php?type=saveActivity&id='+this.selectedResult['id']+'&pressure='+this.pressure+'&activity_start='+this.activity_start+'&details='+this.details).subscribe(response =>{
      if(response['status']=='success'){
        this.getActivities();
        alertify.success("On Time End !!");
      } else{
        alertify.error("Error To End !!")
      }
    });
  }
  startAfterUV(index){
    this.selectedResult = this.results[index];
    this.service.get('microbiology/laf.php?type=startAfterUVLight&id='+this.selectedResult['id']).subscribe(response =>{
      if(response['status']=='success'){
        this.getActivities();
        alertify.success("On Time Starts !!");
      } else{
        alertify.error("Error To Start !!")
      }
    });
  }
  stopAfterUV(index){
    this.selectedResult = this.results[index];
    this.service.get('microbiology/laf.php?type=stopAfterUVLight&id='+this.selectedResult['id']).subscribe(response =>{
      if(response['status']=='success'){
        this.getActivities();
        alertify.success("On Time Starts !!");
      } else{
        alertify.error("Error To Start !!")
      }
    });
  }
  downloadLog(){
    this.service.open('qa/laf.php?type=downloadActivities&from_date=' +this.from_date +'&to_date=' +this.to_date);
  }

}
