import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-feedback',
  templateUrl: './feedback.component.html',
  styleUrls: ['./feedback.component.css']
})
export class FeedbackComponent implements OnInit {

 
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
    this.service.get('training.php?type=gettrainingFeedbackLogForDept&training_category='+this.training_category+'&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
  training_category = 'Level 1 ( Read a Document )';


 

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }


  isOtherDetails1 = false;
   selectedFeedback = [];

  viewDetails(value) {

    this.selectedFeedback = [];
    this.isOtherDetails1 = true;
    this.selectedFeedback = value['feedback_exp'];
 }

 
 

 
}
