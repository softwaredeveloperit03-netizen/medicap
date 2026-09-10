import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  results: any = [];
  selectedResults=[];
  isView = false;
  from_date=''
  to_date='';
  today='';
  constructor(private service: DataAccessService,private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getTankCleaningLog();
  }
  getTankCleaningLog() {
    this.service.get('engineering/watertank.php?type=getTankCleaningLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }
  download(){
    this.service.open('engineering/watertank.php?type=downloadTankCleaningLog&from_date='+this.from_date+'&to_date='+this.to_date);
  }
  view(index){
    this.selectedResults = this.results[index];
  this.isView = true;
  }
}
