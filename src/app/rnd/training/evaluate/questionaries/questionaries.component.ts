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

  results;

  questions = [];
  question = '';
  option1 = '';
  option2 = '';
  option3 = '';
  option4 = '';
  answer = '';

  reports;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingQuestionaries();
    this.getCompletedQuestionaries();
  }

  getPendingQuestionaries() {
    this.service.get('training.php?type=getPendingRDQuestionaries').subscribe(response => {
      this.results = response;
    });
  }

  getCompletedQuestionaries() {
    this.service.get('training.php?type=getCompletedQuestionariesRD').subscribe(response => {
      this.reports = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
    this.ispending = false;

  }

  ispending = false;

  btn(){
    this.isNew = false;
    this.ispending = true;

  }

  viewReport(index) {
    this.selectedResult = this.reports[index];
    this.isView = true;
  }

  addQuestions() {
    let len = this.questions.length;
    let temp = {};
    temp['question'] = this.question;
    temp['option1'] = this.option1;
    temp['option2'] = this.option2;
    temp['option3'] = this.option3;
    temp['option4'] = this.option4;
    temp['answer'] = this.answer;
    this.questions[len] = temp;

    this.question = '';
    this.option1 = '';
    this.option2 = '';
    this.option3 = '';
    this.option4 = '';
    this.answer = '';
  }

  saveQuestionnaries(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['questions'] = this.questions;
    this.service.post('training.php?type=saveQuestionnaries', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.questions = [];
        alert('saved Successfully');
        this.isNew = false;
         this.ispending = true;
        this.getPendingQuestionaries();
        this.getCompletedQuestionaries();
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
