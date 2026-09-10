import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-training',
  templateUrl: './training.component.html',
  styleUrls: ['./training.component.css']
})
export class TrainingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  trainers;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSOPTraining();
  }

  getPendingSOPTraining() {
    this.service.get('sops.php?type=getPendingSOPTraining').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getTrainers();
  }

  getTrainers() {
    this.service.get('training.php?type=getTrainers').subscribe(response => {
      this.trainers = response;
    });
  }

  deleteEmp(i, j) {
    let departments = this.selectedResult['training'];
    let department = departments[i];
    let employees = department['employees'];
    employees.splice(j, 1);
    department['employees'] = employees;
    departments[i] = department;
    this.selectedResult['training'] = departments;
  }

  save() {
    this.service.post('sops.php?type=allocateTraining', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.error('SOP Training allocated to employees');
        this.isView = false;
        this.getPendingSOPTraining();
      } else {
        alertify.success('Failed: An error occured, please try again!');
      }
    });
  }

}
