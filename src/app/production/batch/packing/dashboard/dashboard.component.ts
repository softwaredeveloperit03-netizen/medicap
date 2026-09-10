import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  results;

  from_date = '';
  to_date = '';
  max_date = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getTransferLog();
  }

  getTransferLog() {
    this.service.get('production/transfer.php?type=getTransferLog&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

}
