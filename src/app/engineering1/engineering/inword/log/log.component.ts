import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  inwordlog;
  Results = [];
  
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getInwordLog();
  }

  getInwordLog(){
    this.service.get('engineering/inword.php?type=getInwordsLog').subscribe(response =>{
      this.inwordlog = response;
    });
  }

  view(index){
    this.Results = this.inwordlog[index];
    this.isView = true;
  }
}
