import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-attendance',
  templateUrl: './attendance.component.html',
  styleUrls: ['./attendance.component.css']
})
export class AttendanceComponent implements OnInit {

   isView = false;
  results;
  trainings;
  employees;
  selectedTraining = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAttendanceLog();
    this.getPendingScheduleTrainings();
  }

  getAttendanceLog() {
    this.service.get('training.php?type=getAttendanceLogForDept&training_category=Level 3 ( On The Job Training )&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  getPendingScheduleTrainings() {
    this.service.get('training.php?type=getPendingAttendanceTrainingsForOJT&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.trainings = response;
    });
  }

  ispending = false;
  
  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }


  
  tnemp_id;
  tn_no;
  attendance;

  isPresent = false;
  isAbsent = false;
  selectedemp =[];

  selectedOtherDet =[];
  otherIndex:any;
  openPresent(attendance,i) {

    this.tnemp_id = this.selectedemp['tnemp_id'];
    this.tn_no = this.selectedemp['tn_no'];
    this.attendance = attendance;
    this.otherIndex = i;

    this.selectedOtherDet = this.othersDetailsData1[i];

    if(attendance == 'Present'){
          this.isPresent = true;
    }else{
      this.isAbsent = true;
  

    }

  }




  isResult = false;
  resultData =[];

  viewResult(index){
    this.resultData =[];
    this.isResult = true;
    this.resultData = this.othersDetailsData1[index];
    console.log(this.resultData);
  }
  openComplete(){
     this.isComplete = true;
  }



  isEndTime = false;

 

  cordinator_password='';
  employee_password='';
  training_start_time='';
  training_end_time='';


 
  
  training_start_date ='';
  training_end_date ='';
  
  saveAttendance(data) {
 
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.othersDetailsData1[this.otherIndex].attendance =  this.attendance;
    this.othersDetailsData1[this.otherIndex].training_start_date = this.training_start_date;
    this.othersDetailsData1[this.otherIndex].training_end_date = this.training_end_date;
    this.othersDetailsData1[this.otherIndex].training_start_time = this.training_start_time;
    this.othersDetailsData1[this.otherIndex].training_end_time = this.training_end_time;
    this.othersDetailsData1[this.otherIndex].remark = this.remark;
  
      let temp = {};
      temp['tnemp_id'] = this.tnemp_id;
      temp['tn_no'] =  this.tn_no;
      temp['othersDetailsData1'] =  this.othersDetailsData1;
     
      this.service.post('training.php?type=saveAttendanceForOJT', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getPendingScheduleTrainings();
          alert('Records Saved Successfully');
          this.tnemp_id ='';
          this.tn_no ='';
          this.attendance ='';
          this.isPresent = false;
          data.reset();
         } else {
          alert('An error occured, please try again');
        }
      });

      this.jadu();
    
  }

  CompleteTraining( ) {
  
      let temp = {};
      temp['tnemp_id'] = this.selectedemp['tnemp_id'];
      temp['tn_no'] =  this.selectedemp['tn_no'];
      
      this.service.post('training.php?type=completeOjtAttendance', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getPendingScheduleTrainings();
          alert('Attendance Saved Successfully');
          this.isPresent = false;
          this.isOtherDetails1 = false;
          } else {
          alert('An error occured, please try again');
        }
      });

      this.jadu();
    
  }
 
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.cordinator_password +'&emp_id=' + localStorage.getItem('loger_id')).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
          this.isAbsent = false;
          data.reset();

          this.othersDetailsData1[this.otherIndex].attendance =  this.attendance;
          this.othersDetailsData1[this.otherIndex].training_start_date = 'NA';
          this.othersDetailsData1[this.otherIndex].training_end_date = 'NA'
          this.othersDetailsData1[this.otherIndex].training_start_time = 'NA';
          this.othersDetailsData1[this.otherIndex].training_end_time = 'NA';
          this.othersDetailsData1[this.otherIndex].remark = 'NA';

          let temp = {};
          temp['tnemp_id'] = this.tnemp_id;
          temp['tn_no'] =  this.tn_no;
          temp['othersDetailsData1'] =  this.othersDetailsData1;
         
          this.service.post('training.php?type=saveAttendanceForOJT', JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
               this.getPendingScheduleTrainings();
              alert('Records Saved Successfully');
              this.tnemp_id ='';
              this.tn_no ='';
              this.attendance ='';
              this.jadu();
             } else {
              alert('An error occured, please try again');
            }
          });
           
      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }

  isComplete = false;

  digiSign1(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgntraining&mpin=' + this.cordinator_password +
      '&training_emp_id=' + this.selectedemp['emp_id'] + '&employee_password=' + this.employee_password).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.CompleteTraining();
        this.isComplete = false;
        data.reset()
      }else{
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
 













  processedDetailsData

  isJadu = false;

    jadu(){
  
      this.isJadu = this.othersDetailsData1.every(item => item.attendance === 'Present');
 
    }

 
  remark = '';
 
 
  
  viewDetails(value,i){
    this.othersDetailsData1 =[];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
    this.selectedemp = this.trainings[i];
    this.jadu();
  }
  isOtherDetails1 = false;
  othersDetailsData1 =[];






  
  download(id) {
    this.service.open('purchase/training.php?type=attendanceLog&id='+id);  
   }






}