import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getRacksLog();
  }

  getRacksLog(){
    this.service.get('qa/controlsample.php?type=getRacksLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

}