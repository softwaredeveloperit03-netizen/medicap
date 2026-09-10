import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-result',
  templateUrl: './result.component.html',
  styleUrls: ['./result.component.css']
})
export class ResultComponent implements OnInit {

  isView = false;
  results;

  selectedTraining = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCompletedTrainings();
  }

  getCompletedTrainings() {
    this.service.get('training.php?type=OJTgetemployeeTrainningResult').subscribe(response => {
      this.results = response;
    });
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




}
