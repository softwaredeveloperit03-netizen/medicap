import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  trainings;
  isView= false;
  selectedTraining=[];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getOnJobTrainingLog();
  }

  getOnJobTrainingLog(){
    this.service.get('qa/training.php?type=getOnJobTrainingLog').subscribe(response => {
      this.trainings = response;
    })
  }

  view(index){
    this.selectedTraining = this.trainings[index];
    this.isView= true;

  }

}
