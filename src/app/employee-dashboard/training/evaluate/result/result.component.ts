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
    this.service.get('training.php?type=getemployeeTrainningResult').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

}
