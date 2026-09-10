  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;
@Component({
  selector: 'app-schedule',
  templateUrl: './schedule.component.html',
  styleUrls: ['./schedule.component.css']
})
export class ScheduleComponent implements OnInit {
    isView = false;
    results: any=[];
 
    constructor(private service:DataAccessService) { }
  
    ngOnInit() {
     this.getTankSanitizationSchedule();
    }
  
    getTankSanitizationSchedule(){
      this.service.get('engineering/watertank.php?type=getTankSanitizationSchedule').subscribe(response=>{
        this.results=response;
      });
    }
    download(){
      this.service.open('engineering/watertank.php?type=downloadTankSanitizationSchedule')
    }
  }
  