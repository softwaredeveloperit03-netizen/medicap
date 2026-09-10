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
    this.service.get('training.php?type=getAttendanceLogForDept&training_category=cGMP Training&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  getPendingScheduleTrainings() {
    this.service.get('training.php?type=getPendingAttendanceTrainingsForSep&training_category=cGMP Training&dept_name=' + localStorage.getItem('department')).subscribe(response => {
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
           this.getPendingScheduleTrainings();
           this.getAttendanceLog();

          alert('Records Saved Successfully');
          this.isNew = false 
          this.ispending = true;
        } else {
          alert('An error occured, please try again');
        }
      });
    }
  }


  
  training_start_time = '';
  training_end_time = '';

  
  openendtime(value) {

    

    var d = new Date(),
    year = d.getFullYear(),
    month = ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1),
    day = (d.getDate() < 10 ? '0' : '') + d.getDate(),
    h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
    m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();


    if(value == 'START'){
      this.training_start_time = h + ':' + m;
    }else if(value == 'END'){
      this.training_end_time = h + ':' + m;
    }

    
  }








  download(id) {
    this.service.open('purchase/training.php?type=attendanceLog&id='+id);  
   }




  
}
