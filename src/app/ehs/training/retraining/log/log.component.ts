import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
 
  isView = false;
  results;

  selectedTraining = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRetrainings();
   }

  getPendingRetrainings() {
    this.service.get('training.php?type=getRetrainingLogEHS').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }


  downloadreport(){
    this.service.open('pdf1/training.php?type=trainingRetraininglog');
  }
   

 

}
