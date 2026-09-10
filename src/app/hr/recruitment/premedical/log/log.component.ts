import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  isView=false;
  selectedReport=[];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPremedicalsLog();
  }
  getPremedicalsLog(){
    this.service.get('hr/medical.php?type=getPremedicalsLog').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }
}
