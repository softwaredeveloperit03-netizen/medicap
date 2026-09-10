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
  trainers;
  results;
  trainings;
  employees;
  selectedTraining = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAttendanceLog();
    this.getPendingScheduleTrainings();
    this.getTrainers();
  }

  getAttendanceLog() {
    this.service.get('training.php?type=getAttendanceLog').subscribe(response => {
      this.results = response;
    });
  }
  getTrainers() {
    this.service.get('training.php?type=getTrainers').subscribe((response: any) => {
      this.trainers = response;
    });
  }
  getPendingScheduleTrainings() {
    this.service.get('training.php?type=getPendingAttendanceTrainings').subscribe(response => {
      this.trainings = response;
    });
  }

  viewTraining(index) {
    this.selectedTraining = this.trainings[index];
    this.isNew = true;
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

  addAttendance(index, status) {
    let data = this.selectedTraining['participants'];
    data[index].attendance = status;
    this.selectedTraining['participants'] = data;
  }

  saveAttendance(formData) {
    if (!formData.valid) {
      alert('All fields are required');
      return;
    }
    let flag = 0;
    let data = this.selectedTraining['participants'];
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
      this.selectedTraining['trainer_remark'] = temp['trainer_remark'];
      this.service.post('training.php?type=saveDailyTrainingAttendance', JSON.stringify(this.selectedTraining)).subscribe(response => {
        if (response['status'] == 'success') {
          this.isView = false;
          this.getPendingScheduleTrainings();
          alert('Records Saved Successfully');
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
