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
  selectedReport=[];
  selectedReport2=[];
  isShow=false;
  hide=false;


  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getDevTrials();
  }
  getDevTrials(){
    this.service.get('rnd/optimisation.php?type=getOptimisationsLog').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.selectedReport=this.selectedResult['trials']
    this.isView = true; 
  }
  view1(index){
    this.selectedReport2=this.selectedReport[index];
    this.isShow=true;
    this.isView=false;
  }

}
