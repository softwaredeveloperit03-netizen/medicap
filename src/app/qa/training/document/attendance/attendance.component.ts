import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-attendance',
  templateUrl: './attendance.component.html',
  styleUrls: ['./attendance.component.css']
})
export class AttendanceComponent implements OnInit {

  isNew = false;
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
    this.service.get('training.php?type=getDocAttendanceLog').subscribe(response => {
      this.results = response;
    });
  }

  getPendingScheduleTrainings() {
    this.service.get('training.php?type=getPendingAttendanceDocTrainings').subscribe(response => {
      this.trainings = response;
    });
  }
  ispending = false;
  viewTraining(index) {
    this.selectedTraining = this.trainings[index];
    this.isNew = true;
    this.ispending = false;

   }

   btn(){
    this.isNew = false 
    this.ispending = true;
   }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

  addAttendance(index, status) {
    let data = this.selectedTraining['employees'];
    data[index].attendance = status;
    this.selectedTraining['employees'] = data;
  }

  saveAttendance(formData) {
    if (!formData.valid) {
      alert('All fields are required');
      return;
    }
    let flag = 0;
    let data = this.selectedTraining['employees'];
    for (let i = 0; i < Object.keys(data).length; i++) {
      if (data[i].attendance == 'pending') {
        flag = 1;
        break;
      } else {
        flag = 0;
      }
    }
    if (flag == 1) {
      alert('All employees Attendance is required');
    } else {
      let temp = formData.value;
      this.selectedTraining['training_tool'] = temp['training_tool'];
      this.selectedTraining['training_start_time'] = temp['training_start_time'];
      this.selectedTraining['training_end_time'] = temp['training_end_time'];
      this.service.post('training.php?type=saveAttendance', JSON.stringify(this.selectedTraining)).subscribe(response => {
        if (response['status'] == 'success') {
          this.getAttendanceLog();
          this.getPendingScheduleTrainings();
          alert('Records Saved Successfully');
          this.isNew = false 
          this.ispending = true;
        } else {
          alert('An error occured, please try again');
        }
      });
    }
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=attendencelog');
  }
  downloadattendance(value){
    this.service.open('pdf1/training.php?type=attendenceview&id='+value)
  }
}
