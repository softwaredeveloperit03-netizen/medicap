import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  selectedReport=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getInterviewsLog();
  }
  getInterviewsLog(){
    this.service.get('hr/interview.php?type=getInterviewerLog').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }

}
