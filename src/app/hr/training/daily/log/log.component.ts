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
  trainers
  trainer_name;
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDailyAnnoucementLog();
    this.getTrainers();
  }

  getDailyAnnoucementLog() {
    this.service.get('training.php?type=getDailyAnnoucementLog&trainer_name=' + this.trainer_name).subscribe(response => {
      this.results = response;
    });
  }
  getTrainers() {
    this.service.get('training.php?type=getTrainers').subscribe((response: any) => {
      this.trainers = response;
    });
  }


  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

}
