import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-uncertinity-checking',
  templateUrl: './uncertinity-checking.component.html',
  styleUrls: ['./uncertinity-checking.component.css']
})
export class UncertinityCheckingComponent implements OnInit {

  isView = false;
  results;

  selectedBalance = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckingDailyBalances();
  }

  getCheckingDailyBalances() {
    this.service.get('balance.php?type=getCheckingMonthlyBalances').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('balance.php?type=updateDaily&status=' + status + '&id=' + this.selectedBalance['daily_id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Updated Successfully');
        this.isView = false;
        this.getCheckingDailyBalances();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
