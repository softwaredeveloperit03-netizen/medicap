import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
  providers: [DatePipe]
})
export class HomeComponent implements OnInit {
  
  isUser = false;
  isChecker = false;
  isApprover = false;
  from_date = '';
  to_date = '';
  isView = false;
  results;
  selectedBalance = [];
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');/*
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver'))); */
  }
 
  ngOnInit() {
    this.getCalibrationLog();
  }

  getCalibrationLog() {
    this.service.get('calibration/balance.php?type=getCalibrationLog&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  download() {
    this.service.open('calibration/balance.php?type=downloadCalibrationLog&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  
  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }

}
