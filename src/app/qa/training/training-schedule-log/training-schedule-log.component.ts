import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-training-schedule-log',
  templateUrl: './training-schedule-log.component.html',
  styleUrls: ['./training-schedule-log.component.css']
})
export class TrainingScheduleLogComponent implements OnInit {
  isView = false;
  results;

  selectedTraining = [];
    filteredResults: any[];
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getCompletedTrainings(); 
  }

  getCompletedTrainings() {
    this.service.get('training.php?type=getCompletedTrainings').subscribe(response => {
      this.results = response;
      this.filteredResults = [...this.results];
    });
  }

  view(index) {
    let tempIndex = this.results.findIndex(trainingObj=>trainingObj.id == index)
    this.selectedTraining = this.results[tempIndex];
    this.isView = true;
  }
  goToAttendance(index) {
    const tempIndex = this.results.findIndex(trainingObj=>trainingObj.id == index)
    let trainingData = this.results[tempIndex];
    this.router.navigate(['/qa/training/training-attendance'], { state: { trainingData: trainingData }});
  }

  downloadreport(){
    this.service.open('pdf1/training.php?type=traininglog');
  }

  onCVhangeTrainingType(value:any){
       if (!value) {
        this.filteredResults = [...this.results];
      } else {
        this.filteredResults = this.results.filter(training => training.training_type === value);
      }
  }

  AllRecord() {
    this.filteredResults = [...this.results];
  }

  onSubjectTypeChange(value:any) {
       if (!value) {
        this.filteredResults = [...this.results];
      } else {
        this.filteredResults = this.results.filter(training => training.subject.toLowerCase() === value.toLowerCase());
      }
  }
}
