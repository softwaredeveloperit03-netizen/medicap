import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  selectresult=[];
  isView=false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
   this.getProcesses()
  }
  getProcesses(){
    this.service.get('production/stage.php?type=getstage_master').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectresult = this.results[index];
    this.isView = true;
  }
}
