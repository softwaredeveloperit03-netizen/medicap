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
  training_category='';
  questions = [];
  question = '';
  option1 = '';
  option2 = '';
  option3 = '';
  option4 = '';
  answer = '';
  subject;
  equipments;
  departments;

   constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getCompletedQuestionaries();
    this.getAlllDepartments();
  }

  topic='';
 
  getAlllDepartments() {
    this.service.get('training.php?type=getAllDepartments').subscribe(response => {
      this.departments = response;
    });
  } 

  getequipmentBydept(dept_name) {
    this.service.get('training.php?type=getequipmentBydept&dept_name='+dept_name).subscribe(response => {
      this.equipments = response;
    });
  }

  subject_list;
  getSubject(value) {
    this.service.get('training.php?type=getsubject1&training_category='+value).subscribe(response => {
      this.subject_list = response;
    });
  }

  getCompletedQuestionaries() {
    this.service.get('training.php?type=getAllQestionirries').subscribe(response => {
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
  duration = 30;

  isNumber(value,jadu){

    if (isNaN(value)) {
      alertify.warning('Please Enter Numeric Value!!!!');
      if(jadu == 1){
        this.total_marks = 0;

      }else{
        this.duration = 0;

      }
       return;
    }
  }






}
