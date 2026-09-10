import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  isView = false;
  results;
  selectresults = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getLog();
  }
  getLog(){
    this.service.get('qc/validation/process.php?type=getProcessesLog').subscribe(response =>{
      this.results = response;
    });
  }
  view(index){
    this.selectresults =this.results[index];
    this.isView = true;
  }

}
