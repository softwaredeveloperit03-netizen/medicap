import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-train',
  templateUrl: './train.component.html',
  styleUrls: ['./train.component.css']
})
export class TrainComponent implements OnInit {

  isView = false;
  results;
  trainings;
  employees;
  selectedTraining = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getPendingScheduleTrainings();
  }

 

  getPendingScheduleTrainings() {
    this.service.get('training.php?type=getOJTForPractical&dept_name=' + localStorage.getItem('department')).subscribe(response => {
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
 

  isEndTime = false;
   

  
 

  saveAttendance() {
 
      let temp = {};
      temp['tnemp_id'] = this.tnemp_id;
      temp['tn_no'] =  this.tn_no;
      temp['attendance'] =  this.attendance;
  
      this.service.post('training.php?type=saveAttendanceForOJT', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
           this.getPendingScheduleTrainings();
          alert('Records Saved Successfully');
          this.tnemp_id ='';
          this.tn_no ='';
          this.attendance ='';
          } else {
          alert('An error occured, please try again');
        }
      });
    
  }

 


}