import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-daily-checking',
  templateUrl: './daily-checking.component.html',
  styleUrls: ['./daily-checking.component.css']
})
export class DailyCheckingComponent implements OnInit {

  isView = false;
  results;

  selectedBalance = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckingDailyBalances();
  }

  getCheckingDailyBalances() {
    this.service.get('balance.php?type=getCheckingDailyBalances').subscribe(response => {
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
        alertify.success('Daily Verification Updated Successfully');
        this.isView = false;
        this.getCheckingDailyBalances();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
