import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-questioneries',
  templateUrl: './questioneries.component.html',
  styleUrls: ['./questioneries.component.css']
})
export class QuestioneriesComponent implements OnInit {

  isView = false;
  isNew = false;
  entries;
  selectedResult = [];

  trainings;
  training_category = 'Level 1 ( Read a Document )';


  reports;
  constructor(private service: DataAccessService) { }
 
  ngOnInit() {
    this.getPendingQuestionaries();
    this.getquestions();
   }

   questionData;
  
  getPendingQuestionaries() {
    this.service.get('training.php?type=getQuestionnierForDept&training_category='+ this.training_category +'&dept_name=' + localStorage.getItem('department')).subscribe(response => {
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
 


  questions;

  saveQuestionnaries(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['tnNo'] = this.selectedResult['id'];
    temp['total_marks'] = this.selectedQuestion['total_marks'];
    temp['duration'] = this.selectedQuestion['duration'];
    temp['evaluator_name'] = this.selectedQuestion['evaluator_name'];
    temp['questions'] = this.selectedQuestion['questions'];
    temp['questionsForEmp'] = this.selectedQuestion;
    this.service.post('training.php?type=saveQuestionnariesForALL', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alert('saved Successfully');
        this.isView = false;
        this.getPendingQuestionaries();
       } else {
        alert('An error occured');
      }
    });
  }

 


}
