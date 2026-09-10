import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  providers: [DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  fromdate;
  todate;
  results;

  selectedReport = [];
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
  ngOnInit() {
    this.getChangeControlsDept();
  }
  getChangeControlsDept() {
    this.service.get('/management/changecontrol.php?type=getChangeControlsDept&fromdate='+this.fromdate+'&todate='+this.todate).subscribe((response:any) => {
      this.results = response;
    });
  }
  clearrecords(){
    
  }
  getprint(){
    this.service.open('pdf1/changecontrol.php?type=changecontrolDeptlog&fromdate='+this.fromdate+'&todate='+this.todate);
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
}