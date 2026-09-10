import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getRequirementsLog();
  }

  getRequirementsLog() {
    this.service.get('planning/consoladated.php?type=getRequirementsLog').subscribe(response=>{
      this.results=response;
    });
  }
  download() {
    this.service.open('planning/consoladated.php?type=downloadRequirementsLog')
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView=true;
  }

}
