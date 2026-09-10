import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-annoucement',
  templateUrl: './annoucement.component.html',
  styleUrls: ['./annoucement.component.css']
})
export class AnnoucementComponent implements OnInit {
  ispending = false;
  isView = false;
  results;
  isNewTraining = false;
  trainers;
  trainings;
  employees;
  selectedTraining = [];
  employeeList = [];
  selectedEmp = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getScheduleLog();
    this.getTrainings();
  }

  getScheduleLog() {
    this.service.get('training.php?type=getScheduleLogAnnouncementLog').subscribe(response => {
      this.results = response;
    });
  }

  getTrainings() {
    this.service.get('training.php?type=getScheduleLog_announcement').subscribe(response => {
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
        this.employeeList = [];
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
