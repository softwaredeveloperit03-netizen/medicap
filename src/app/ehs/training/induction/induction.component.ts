import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-induction',
  templateUrl: './induction.component.html',
  styleUrls: ['./induction.component.css']
})
export class InductionComponent implements OnInit {
  isView = false;
  results;

  selectedTraining = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCompletedTrainings();
  }

  getCompletedTrainings() {
    this.service.get('training.php?type=getCompletedEHSTrainings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=traininglog');
  }
}
