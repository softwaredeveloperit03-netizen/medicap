import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css'],
  providers:[DatePipe]
})
export class ReportComponent implements OnInit {

  results;
  from_date='';
  to_date='';
  today='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getDispensingLog();
  }

  getDispensingLog(){
    this.service.get('store/dispensing.php?type=getDispensingLog&product_type=&to_date='+this.to_date+'&from_date='+this.from_date).subscribe(response=>{
      this.results=response;
    });
  }

  download(){
    this.service.open('store/dispensing.php?type=downloadDispensingReport&to_date='+this.to_date+'&from_date='+this.from_date);
  }

}
