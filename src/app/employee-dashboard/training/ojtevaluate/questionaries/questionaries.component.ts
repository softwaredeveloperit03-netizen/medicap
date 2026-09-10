import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 



@Component({
  selector: 'app-questionaries',
  templateUrl: './questionaries.component.html',
  styleUrls: ['./questionaries.component.css']
})
export class QuestionariesComponent implements OnInit {

  isView = false;
   entries;
  selectedTraining = [];
  temp = [];
 
  results;

  questions ;
 
 

  reports;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingQuestionaries();
   }

  getPendingQuestionaries() {
    this.service.get('training.php?type=OJTemployeequestions').subscribe(response => {
      this.results = response;
    });
  }
 



  
  isOtherDetails1 = false;
  othersDetailsData1 = [];
  selectedemp = [];

  viewDetails(value, i) {
    this.othersDetailsData1 = [];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
    this.selectedemp = this.results[i]; // Assuming `trainings` is defined somewhere
  }

  trackByIndex(index: number, item: any): number {
    return index;
  }
 

  selectedans;
  checkanswser =[];
  selectedqpaper =[];

  view(index ) {
    this.selectedTraining = this.othersDetailsData1[index];
    this.selectedqpaper = this.selectedTraining['questions'];
    this.isView = true;
    this.isOtherDetails1 = false;
    this.jaduIndex = index;
  }

  jaduIndex;

  saveQuestionnaries(data) {

    if(!data.valid){
      alert('All Questions Manditory!!!!!');
      return;
    }
 
 
    let len;
 
       let totalmark = this.selectedqpaper['total_marks']

       let length = this.selectedqpaper['questions'].length;

       let markPerQuestion = totalmark / length;


       let markGetInExam = 0;
       for(let i =0; i< this.selectedqpaper['questions'].length;i++){
         if(this.selectedqpaper['questions'][i].answer == this.selectedqpaper['questions'][i].emp_answer){
          markGetInExam = markGetInExam + +markPerQuestion;
         }
       }
 
       let passcriteria = totalmark * 0.8;
 
       console.log("totalmark"+totalmark);
       console.log("passcriteria"+passcriteria);
       console.log("mark per que"+markPerQuestion);
 
       console.log("secuMark"+markGetInExam);
 
        let res = '';
        markGetInExam = parseFloat(markGetInExam.toFixed(2));

       if(markGetInExam >= passcriteria){
        res = 'Pass';
       }else{
        res = 'Fail';
       } 
       console.log("result"+res);


       let temp ={};
       temp['result'] = res;
       temp['secuMark'] = markGetInExam;
       temp['totalmark'] = totalmark;
       temp['questions'] = this.selectedqpaper['questions'];
 
       this.othersDetailsData1[this.jaduIndex].result = res;
       this.othersDetailsData1[this.jaduIndex].secuMark = markGetInExam;
       this.othersDetailsData1[this.jaduIndex].totalmark = totalmark;
       this.othersDetailsData1[this.jaduIndex].exam = 'Complete';
       this.othersDetailsData1[this.jaduIndex].answser = this.selectedqpaper['questions'];
     
     
    this.service.post('training.php?type=saveOJTEMployeeExamResult&id='+this.selectedemp['tnId'] , JSON.stringify(this.othersDetailsData1)).subscribe(response => {
      if (response['status'] == 'success') {
         alert('saved Successfully');
         this.isView = false;
         this.getPendingQuestionaries();
         } else {
        alert('An error occured');
      }
    });


  }

  complete() {

  
    let temp ={};

    this.service.post('training.php?type=complteOjtEvaluation&id='+this.selectedemp['tnId'] , JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
         alert('saved Successfully');
         this.isView = false;
         this.isOtherDetails1 = false;
         this.getPendingQuestionaries();
         } else {
        alert('An error occured');
      }
    });


  }
 
}



