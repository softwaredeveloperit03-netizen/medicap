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

 
  isView = false;
   results;
 
  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
     this.getbreakdown_data(); 
  }

  
  getbreakdown_data(){
    this.service.get('engineering/maintenance.php?type=getebmLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
   

 

}
