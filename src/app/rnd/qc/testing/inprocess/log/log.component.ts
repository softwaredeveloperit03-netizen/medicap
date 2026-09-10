import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

 
  isView = false;
  isTest = false;
  results;
  tests;

  selectedResult = [];
  selectedTesting = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getTestingsLog();
  }

  getTestingsLog(){
    this.service.get('production/technical.php?type=getTestingsLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.tests = this.selectedResult['tests'];
    this.isView = true;
  }

  viewTest(index){
    this.selectedTesting = this.tests[index];
    this.isView = true;
    this.isTest = true;
  }

}
