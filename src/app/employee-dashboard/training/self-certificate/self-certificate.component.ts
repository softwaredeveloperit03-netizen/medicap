import { Component, OnInit } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-self-certificate',
  templateUrl: './self-certificate.component.html',
  styleUrls: ['./self-certificate.component.css']
})
export class SelfCertificateComponent implements OnInit {

  loading;
  

 
  isView = false;
  results;

  isNewTraining = false;
  selectedTraining = [];
    constructor(private router: Router,private service: DataAccessService,private sanitizer: DomSanitizer) { }

  ngOnInit() {
    this.getScheduleLog();
     }

  getScheduleLog() {
    this.service.get('training.php?type=getSelfLearningEmployee').subscribe(response => {
      this.results = response;
     });
  }

  selecteddTopic=[];

  proccedForSelfLearning(fileNm,i){
    
    this.initialTime=0;
    const url = 'https://paperlessgmp.in/phpCyclone/upload/documentIndex/' + fileNm;
    this.pdfSrc = this.sanitizer.bypassSecurityTrustResourceUrl(url);
    this.isProceed = true;

    this.selecteddTopic = this.results[i];
 
    this.initialTime = this.selecteddTopic['slTime'];
    this.startTimer();
    console.log(this.pdfSrc);

    this.proceedlerning(this.selecteddTopic['tn_no'],this.selecteddTopic['tnemp_id']);

 
  }

  pdfSrc;
  retraining(fileNm,i){
    
    this.initialTime=0;
    const url = 'https://paperlessgmp.in/phpCyclone/upload/documentIndex/' + fileNm;
    this.pdfSrc = this.sanitizer.bypassSecurityTrustResourceUrl(url);
    this.isProceed = true;

    this.selecteddTopic = this.results[i];
  
    this.initialTime = this.selecteddTopic['slTime'];
    this.startTimer();
    console.log(this.pdfSrc);
 
  }
 

  proceedlerning(tn_no,tnemp_id) {
 
 
 
      let temp = {};
      temp['tnemp_id'] = this.selecteddTopic['tnemp_id'];
      temp['tn_no'] =  this.selecteddTopic['tn_no'];
       this.service.post('training.php?type=saveAttendanceSelfLearning&tnemp_id='+tnemp_id+'&tn_no='+tn_no, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getScheduleLog();
          alert('Self Learning Started');
           } else {
          alert('An error occured, please try again');
        }
      });
  }



  completeTraining() {
  
      let temp = {};
      temp['tnemp_id'] = this.selecteddTopic['tnemp_id'];
      temp['tn_no'] =  this.selecteddTopic['tn_no'];
      
      this.service.post('training.php?type=CompleteAttendanceSelfLearning', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getScheduleLog();
          alert('Self Learning Saved Successfully');
          this.isProceed = false;
           } else {
          alert('An error occured, please try again');
        }
      });
    
  }

 

  isPaused = false;
  isProceed = false;
 
  initialTime: number = 30; // Time in minutes
  remainingTime: number; // Remaining time in seconds
  intervalId: any;
 
  ngOnDestroy() {
    this.clearTimer();
  }

  
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

  pauseTimer() {
    this.isPaused = true;
    this.clearTimer();
  }

  resumeTimer() {
    this.isPaused = false;
    this.runTimer();
  }

 
  clearTimer() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
    }
  }

  timerFinished() {
   // this.completeTraining();
    console.log('Timer finished!');
    this.isProceed = false;
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


 
   
}
