import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  results;
  from_date = '';
  to_date = '';

  constructor(private service:DataAccessService,private datePipe:DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getTemperatureLog();
  }

  getTemperatureLog(){
    this.service.get('temperature.php?type=getTemperatureLog&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  download(){
    this.service.open('temperature.php?type=downloadTemperatureLog&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

}
