import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-logbook',
  templateUrl: './logbook.component.html',
  providers: [DatePipe]
})
export class LogbookComponent implements OnInit {
  dataList = [];
  fromdate;
  todate;
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
  ngOnInit() {
    this.getChangeControls();
  }
  getChangeControls() {
    this.service.get('capa.php?type=getcapalog&fromdate='+this.fromdate+'&todate='+this.todate).subscribe((response:any) => {
      this.dataList = response;
    });
  }
  clearrecords(){
    
  }
  getprint(){
    this.service.open('pdf1/capa.php?type=capalog&fromdate='+this.fromdate+'&todate='+this.todate);
  }
}