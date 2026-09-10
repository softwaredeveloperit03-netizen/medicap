import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  isNew=false;
  selectedReport=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getRejected();
  }

  getRejected(){
    this.service.get('store/rejection.php?type=getRejectionsLog').subscribe(response=>{
      this.results=response;
    });
  }

  showReport(index){
    this.selectedReport = this.results[index];
    this.isNew = true;
  }

}
