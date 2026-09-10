import { Component, OnInit } from '@angular/core';
import { DomSanitizer ,SafeResourceUrl } from '@angular/platform-browser';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

   results;
  trainings;
   selectedTraining = [];
  constructor(private service: DataAccessService,private sanitizer: DomSanitizer) { }

  ngOnInit() {
     this.getPendingScheduleTrainings();
  }

 
  getPendingScheduleTrainings() {
    this.service.get('training.php?type=getPendingAttendanceTrainingForEmployee&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.trainings = response;
    });
  }
 
  isProceed = false;
  resultData =[];
  pdfSrc ;
  selecteddTopic=[];


 
  processedDetailsData

  isJadu = false;
  selectedemp;
  
  viewDetails(value,i){
    this.othersDetailsData1 =[];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
    this.selectedemp = this.trainings[i];
  }
  isOtherDetails1 = false;
  othersDetailsData1 =[];

  isPaused = false;

  otherIndex;
  tnemp_id = '';
  tn_no = '';

  proccedForSelfLearning(fileNm,i){
    
    this.initialTime=0;
    const url = 'https://paperlessgmp.in/phpCyclone/upload/documentIndex/' + fileNm;
    this.pdfSrc = this.sanitizer.bypassSecurityTrustResourceUrl(url);
    this.isProceed = true;

    this.selecteddTopic = this.othersDetailsData1[i];
    this.otherIndex = i;

    this.initialTime = this.selecteddTopic['timing'];
    this.startTimer();
    console.log(this.pdfSrc);

    this.proceedlerning(this.selectedemp['tn_no'],this.selectedemp['tnemp_id']);

 
  }

  retraining(fileNm,i){
    
    this.initialTime=0;
    const url = 'https://paperlessgmp.in/phpCyclone/upload/documentIndex/' + fileNm;
    this.pdfSrc = this.sanitizer.bypassSecurityTrustResourceUrl(url);
    this.isProceed = true;

    this.selecteddTopic = this.othersDetailsData1[i];
    this.otherIndex = i;
 
    this.initialTime = this.selecteddTopic['timing'];
    this.startTimer();
    console.log(this.pdfSrc);
 
  }
 
  
  proceedlerning(tn_no,tnemp_id) {

    
    const now = new Date();
    let training_start_date = now.toLocaleDateString();
    const hours = now.getHours().toString().padStart(2, '0'); 
    const minutes = now.getMinutes().toString().padStart(2, '0');  
    let training_start_time = `${hours}:${minutes}`; 
 

    this.othersDetailsData1[this.otherIndex].attendance =  'Reading';
    this.othersDetailsData1[this.otherIndex].training_start_date = training_start_date;
     this.othersDetailsData1[this.otherIndex].training_start_time = training_start_time;
     this.othersDetailsData1[this.otherIndex].remark = 'NA';
      let temp = {};
      temp['tnemp_id'] = this.selectedemp['tnemp_id'];
      temp['tn_no'] =  this.selectedemp['tn_no'];
      temp['othersDetailsData1'] =  this.othersDetailsData1;
      this.service.post('training.php?type=saveAttendanceForOJTfrorEmp&tnemp_id='+tnemp_id+'&tn_no='+tn_no, JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getPendingScheduleTrainings();
          alert('Self Learning Started');
          this.otherIndex =0;
          this.isOtherDetails1 = false;
         } else {
          alert('An error occured, please try again');
        }
      });
  }

  completeTraining(fileNm,i) {

    const now = new Date();
    let training_end_date = now.toLocaleDateString();  
     const hours = now.getHours().toString().padStart(2, '0'); 
    const minutes = now.getMinutes().toString().padStart(2, '0');  
    let training_end_time = `${hours}:${minutes}`; 
 
    this.othersDetailsData1[i].attendance =  'Present';
     this.othersDetailsData1[i].training_end_date = training_end_date;
     this.othersDetailsData1[i].training_end_time = training_end_time;
     
      let temp = {};
      temp['tnemp_id'] = this.selectedemp['tnemp_id'];
      temp['tn_no'] =  this.selectedemp['tn_no'];
      temp['othersDetailsData1'] =  this.othersDetailsData1;
     
      this.service.post('training.php?type=saveAttendanceForOJTfrorEmp', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getPendingScheduleTrainings();
          alert('Records Saved Successfully');
          this.otherIndex =0;
          this.isOtherDetails1 = false;
          } else {
          alert('An error occured, please try again');
        }
      });
    
  }






 
 
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