import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-questionaries',
  templateUrl: './questionaries.component.html',
  styleUrls: ['./questionaries.component.css']
})
export class QuestionariesComponent implements OnInit {

  isView = false;
  isNew = false;
  entries;
  selectedResult = [];

  trainings;

 

  reports;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingQuestionaries();
    this.getquestions();
   }

   questionData;
  
  getPendingQuestionaries() {
    this.service.get('training.php?type=getPendinQuestionariesForOJT&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.trainings = response;
    });
  }
  getquestions() {
    this.service.get('training.php?type=getquestions&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.questionData = response;
    });
  }
 

  view(index) {
    this.selectedResult = this.trainings[index];
    this.isView = true;
  }

  selectedQuestion =[];

  selectQuestion(index){

    this.selectedQuestion = this.questionData[index-1];
  }
 



  isOtherDetails1 = false;
  othersDetailsData1 = [];
  selectedemp = [];

  viewDetails(value, i) {
    this.othersDetailsData1 = [];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
    this.selectedemp = this.trainings[i]; // Assuming `trainings` is defined somewhere
  }

  trackByIndex(index: number, item: any): number {
    return index;
  }




  onQuestionChange(selectedQuestion: any, index: number) {
    this.othersDetailsData1[index].questions = selectedQuestion;
  }


  questions;

  saveQuestionnaries(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    this.service.post('training.php?type=saveQuestionnariesForOJT&tnemp_id='+this.selectedemp['tnemp_id'], JSON.stringify(this.othersDetailsData1)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alert('saved Successfully');
        this.isOtherDetails1 = false;
        this.getPendingQuestionaries();
       } else {
        alert('An error occured');
      }
    });
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=questionaries');
  }
  downloadview(value){
    this.service.open('pdf1/training.php?type=questionariesview&id='+value);
  }
}
