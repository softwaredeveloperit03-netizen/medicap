import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

   isView = false;
  results;
  isNewTraining = false;
  trainers;
  trainings;
  employees;
  selectedTraining = [];
 
  selectedEmp = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getOJTLog();
   }

  getOJTLog() {
    this.service.get('training.php?type=getOjtLog&training_category=Level 3 ( On The Job Training )&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
 

 

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }


  isOtherDetails1 = false;
  othersDetailsData1 = [];
  selectedemp = [];

  viewDetails(value, i) {
    this.othersDetailsData1 = [];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
    this.selectedemp = this.results[i]; // Assuming `trainings` is defined somewhere
  }

  trackByIndex(index: number, item: any): number {
    return index;
  }


 
  download(id) {
    this.service.open('purchase/training.php?type=onJobSchedule&id='+id);  
   }

 
}
