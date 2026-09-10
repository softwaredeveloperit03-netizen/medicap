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

  results;
  from_date='';
  to_date='';
  today='';
  selectedResult=[];
  isView=false;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getIntimationLog();
  }
  getIntimationLog(){
    this.service.get('ipqc/finish.php?type=getIntimationLog&from_date='+this.from_date+ '&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  download(){
    this.service.open('ipqc/finish.php?type=downloadIntimationRecord&id='+this.selectedResult['id']);
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
