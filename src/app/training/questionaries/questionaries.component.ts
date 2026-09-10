import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
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

  equipments;
  department ='';
   constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getCompletedQuestionaries();
    this.getequipmentBydept();
    this.department = localStorage.getItem('department');
  }

  topic='';
 
  getequipmentBydept() {
    this.service.get('training.php?type=getequipmentBydept&dept_name='+localStorage.getItem('department')).subscribe(response => {
      this.equipments = response;
    });
  }

  getCompletedQuestionaries() {
    this.service.get('training.php?type=getqestionirries&dept_name='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  delquestion(i){
    this.questions.splice(i,1);
  }
 
  addQuestions(data) {
 
    if (!data.valid) {
      alertify.warning('All fields are required');
      return;
    }
 
    let len = this.questions.length;
    let temp = {};
    temp['question'] = this.question ;
    temp['option1'] = this.option1;
    temp['option2'] = this.option2;
    temp['option3'] = this.option3 || '-';
    temp['option4'] = this.option4 || '-';
    temp['answer'] = this.answer;
    this.questions.push(temp);
    data.reset();
    console.log(this.questions);
  }

  saveQuestionnaries(data) {
    if (!data.valid) {
      alertify.warning('All fields are required');
      return;
    }

    let temp = data.value;

     temp['questions'] = this.questions;
     this.service.post('training.php?type=saveQuestionMaster', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.questions = [];
        alertify.success(this.service.t('common.savedSuccess'));
        this.isNew = false;
         this.getCompletedQuestionaries();
      } else {
        alertify.error('An error occured');
      }
    });
  }


  downloadreport(){
    this.service.open('training.php?type=questionaries');
  }
  downloadview(value){
    this.service.open('training.php?type=questionariesview&id='+value);
  }

  total_marks = 0;

  isNumber(value){

    if (isNaN(value)) {
      alertify.warning('Please Enter Numeric Value!!!!');
      this.total_marks = 0;
      return;
    }
  }






}
