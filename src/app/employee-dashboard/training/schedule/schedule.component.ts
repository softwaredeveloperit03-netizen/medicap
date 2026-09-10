import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-schedule',
  templateUrl: './schedule.component.html',
  styleUrls: ['./schedule.component.css']
})
export class ScheduleComponent implements OnInit {

  isView = false;
  results;
  isNewTraining = false;
  selectedTraining = [];
    constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getScheduleLog();
     }

  getScheduleLog() {
    this.service.get('training.php?type=employeegetScheduleLog').subscribe(response => {
      this.results = response;
    });
  }
 

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

  
}
