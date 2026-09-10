import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-withdrawal',
  templateUrl: './withdrawal.component.html',
  styleUrls: ['./withdrawal.component.css']
})
export class WithdrawalComponent implements OnInit {
  results;

  selectedStability = [];
  isView = false;
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSamplings();
  }

  getPendingSamplings() {
    this.service.get('stability.php?type=getPendingSamplings').subscribe(response => {
      this.results = response;

      for (let i = 0; i < this.results.length; i++) {
        let result = this.results[i];
        let condition = result['condition'];
        try {
          let intervals = condition['interval'];
          for (let j = 0; j < intervals.length; j++) {
            let interval = intervals[j];
            if (interval['date'] >= result['interval_date']) {
              result['interval'] = interval;
              break;
            }
          }
        } catch (err) {}
        this.results[i] = result;
      }
    });
  }

  viewSampling(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

}
