import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-announcement',
  templateUrl: './announcement.component.html',
  styleUrls: ['./announcement.component.css']
})
export class AnnouncementComponent implements OnInit {

  ispending = false;
  isView = false;
  results;
  isNewTraining = false;
  trainers;
  trainings;
  employees;
  selectedTraining = [];
 
  selectedEmp = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getScheduleLog();
    this.getTrainings();
  }

  getScheduleLog() {
    this.service.get('training.php?type=getScheduleLogAnnouncementLogForDept&training_category=cGMP Training&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  getTrainings() {
    this.service.get('training.php?type=getScheduleLog_announcementForDept&training_category=cGMP Training&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.trainings = response;
    });
  }



  viewTraining(index) {
    this.selectedTraining = this.trainings[index];
    this.isNewTraining = true;
    this.ispending = false;
    
  }

  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }


  saveScheduleTraining(data) {
    let test = data.value;
    test['id'] = this.selectedTraining['id'];
   
    this.service.post('training.php?type=AnnounceTraining', JSON.stringify(test)).subscribe(response => {
      if (response['status'] == 'success') {
        this.selectedTraining = [];
        
        this.selectedEmp = [];
        this.isNewTraining = false;
        this.getScheduleLog();
        this.getTrainings();
        this.ispending = true;
        alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }


  downloadreport(){
    this.service.open('pdf1/training.php?type=trainingschedulelog');
  }
}
