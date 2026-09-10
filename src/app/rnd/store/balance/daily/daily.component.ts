import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-daily',
  templateUrl: './daily.component.html',
  styleUrls: ['./daily.component.css']
})
export class DailyComponent implements OnInit {

  isView = false;
  results;
  selectedBalance = [];

  weights = [
    { "id": 1, "standard": 0, "display": 0},
    { "id": 2, "standard": 0, "display": 0},
    { "id": 3, "standard": 0, "display": 0},
    { "id": 4, "standard": 0, "display": 0},
    { "id": 5, "standard": 0, "display": 0}
  ];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDailyBalances();
  }

  getPendingDailyBalances() {
    this.service.get('balance.php?type=getPendingDailyBalances').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('An error occured, please try again!');
      return;
    }
    this.selectedBalance['weights'] = this.weights;
    this.selectedBalance['deviation'] = 0;
    this.service.post('balance.php?type=saveDailyVerification', JSON.stringify(this.selectedBalance)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Balance Calibration Records Saved Successfully');
        this.isView = false;
        this.getPendingDailyBalances();
        this.weights = [
          { "id": 1, "standard": 0, "display": 0},
          { "id": 2, "standard": 0, "display": 0},
          { "id": 3, "standard": 0, "display": 0},
          { "id": 4, "standard": 0, "display": 0},
          { "id": 5, "standard": 0, "display": 0}
        ];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }
}
