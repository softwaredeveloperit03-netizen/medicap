import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deviation',
  templateUrl: './deviation.component.html',
  styleUrls: ['./deviation.component.css'],
  providers:[DatePipe]
})
export class DeviationComponent implements OnInit {
  fromdate;
  todate;
  results = [];

  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getDeviations();
  }
  
  getDeviations() {
    this.service.get('deviation.php?type=getDeviation&fromdate='+this.fromdate+'&todate='+this.todate).subscribe((response:any) => {
      this.results = response;
    });
  }

  getprint(){
    this.service.open('pdf1/deviation.php?type=deviationLog&fromdate='+this.fromdate+'&todate='+this.todate);
  }
  
  clearrecords(){}

}
