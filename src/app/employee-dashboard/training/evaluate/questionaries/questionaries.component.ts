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
    this.service.get('training.php?type=employeequestions').subscribe(response => {
      this.results = response;
    });
  }
 
  selectedans;
  checkanswser =[];
  selectedqpaper =[];

  view(index ) {
    this.selectedTraining = this.results[index];
     this.selectedqpaper =  this.selectedTraining['questions'];

     for (let i = this.selectedqpaper.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [this.selectedqpaper[i], this.selectedqpaper[j]] = [this.selectedqpaper[j], this.selectedqpaper[i]];
    }

    this.isView = true;

    this.initialTime = this.selectedTraining['duration'] || 20;
    this.startTimer();
  }







  

  initialTime: number = 30; // Time in minutes
  remainingTime: number; // Remaining time in seconds
  intervalId: any;
 
  ngOnDestroy() {
    this.clearTimer();
  }

 
  isPaused = false;

  startTimer() {
    this.remainingTime = this.initialTime * 60; // Convert minutes to seconds
    this.isPaused = false;
    this.runTimer();
  }

  runTimer() {
    this.intervalId = setInterval(() => {
      if (this.remainingTime > 0 && !this.isPaused) {
        this.remainingTime--;
      } else if (this.remainingTime <= 0) {
        this.timerFinished();
        this.clearTimer();
      }
    }, 1000);
  }

 



 
  clearTimer() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
    }
  }

  timerFinished() {
    this.saveQuestionnaries('a');
    console.log('Timer finished!');
    this.isView = false;
   // alert('Time is up!');
  }

  formatTime(): string {
    const minutes: number = Math.floor(this.remainingTime / 60);
    const seconds: number = this.remainingTime % 60;
    return `${this.padZero(minutes)}:${this.padZero(seconds)}`;
  }

  padZero(num: number): string {
    return num < 10 ? '0' + num : num.toString();
  }


 
 

  saveQuestionnaries(data) {

   
 
 
    let len;
 
       let totalmark = this.selectedTraining['marks']

       let length = this.selectedqpaper.length;

       let markPerQuestion = totalmark / length;


       let markGetInExam = 0;
       for(let i =0; i< this.selectedqpaper.length;i++){
         if(this.selectedqpaper[i].answer == this.selectedqpaper[i].emp_answer){
          markGetInExam = markGetInExam + +markPerQuestion;
         }
       }
 
       let passcriteria = totalmark * 0.8;
 
       console.log("totalmark"+totalmark);
       console.log("passcriteria"+passcriteria);
       console.log("mark per que"+markPerQuestion);
 
       console.log("secuMark"+markGetInExam);
 
        let res = '';

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
       temp['questions'] = this.selectedqpaper;
 

    
     
    this.service.post('training.php?type=saveemployeequestionsanswer&id='+this.selectedTraining['tnId'] , JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
         alert('saved Successfully');
         this.isView = false;
         this.getPendingQuestionaries();
         } else {
        alert('An error occured');
      }
    });


  }
 
}



