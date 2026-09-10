import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  selectedResult = [];
  isView = false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getMFRLog();
  }

  getMFRLog() {
    this.service.get('production/master.php?type=getMFRLog').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(){
    this.service.open('production/master.php?type=downloadMFRLog');
  }

}
