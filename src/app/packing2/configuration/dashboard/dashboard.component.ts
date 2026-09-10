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
    this.getConfigurationsLog();
  }

  getConfigurationsLog(){
    this.service.get('packing/configuration.php?type=getConfigurationsLog').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
