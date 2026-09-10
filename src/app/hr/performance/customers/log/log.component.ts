import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView=false;
  selectedCheckList=[];
  results;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  view(index) {
    this.selectedCheckList = this.results[index];
    this.isView = true;
  }
}
