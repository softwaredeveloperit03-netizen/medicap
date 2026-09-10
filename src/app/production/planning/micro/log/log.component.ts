import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  isNew = false;
  results;
  stages=[];

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPlan();
  }


  getPlan(){
    this.service.get('planning/micro.php?type=getPlansLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.stages=JSON.parse(this.selectedResult['stages']);
    this.isNew = true;
  }

}
