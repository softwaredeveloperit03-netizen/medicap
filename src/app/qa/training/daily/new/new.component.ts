import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
 
  selectedResult=[];
  trainers = [];
  training = [];
  labours;
  results;
  isView = false;
  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit(): void {
    this.getTrainers();
    this.getLabours();
  }

  getTrainers() {
    this.service.get('training.php?type=getExternalTrainers').subscribe((response: any) => {
      this.trainers = response;
    });
  }
  getdailytraining() {
    this.service.get('training.php?type=getPendingDailyAnnoucements').subscribe((response: any) => {
      this.training = response;
    });
  }

  getLabours() {
    this.service.get('training.php?type=getLabours').subscribe(response => {
      this.labours = response;
    });
  }

  selectedEmp = [];
  participants = [];


  addEmployees() {
    if (this.selectedEmp.length !== 0) {
      let len = Object.keys(this.participants).length;
      this.participants[len] = this.selectedEmp;
      this.selectedEmp = [];
    }
  }


  selectEmp(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEmp= this.labours[index];
    }
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  change(value, index) {
    this.labours[index].status = value;
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
 
    temp['participants'] = this.participants;
    this.service.post('training.php?type=saveDailyAnnoucement', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/qa/training/daily/annoucement']);
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
