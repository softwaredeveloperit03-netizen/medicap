import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-uncertinity',
  templateUrl: './uncertinity.component.html',
  styleUrls: ['./uncertinity.component.css']
})
export class UncertinityComponent implements OnInit {

  isView = false;
  results;
  selectedBalance = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingMonthlyBalances();
  }

  getPendingMonthlyBalances() {
    this.service.get('balance.php?type=getPendingMonthlyBalances').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alert('An error occured, please try again!');
      return;
    }
    this.selectedBalance['weights'] = this.selectedBalance['weights'];
    this.selectedBalance['deviation'] = 0;
    this.service.post('balance.php?type=saveDailyVerification', JSON.stringify(this.selectedBalance)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Balance Calibration Records Saved Successfully');
        this.isView = false;
        this.getPendingMonthlyBalances();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
