import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  fromdate;
  todate;

  results = [];

  selectedDev = [];
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getDeviations();
  }

  getDeviations() {
    this.service.get('deviation.php?type=getDeviationsLog').subscribe((response:any) => {
      this.results = response;
    });
  }

  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }

  getprint() {
    this.service.open('pdf1/deviation.php?type=deviationlogDept&fromdate='+this.fromdate+'&todate='+this.todate);
  }

}
