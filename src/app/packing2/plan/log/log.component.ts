import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  primary=[];
  secondary=[];
  tertiary=[];


  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingPlans();
  }
  getPendingPlans(){
    this.service.get('packing/plan.php?type=getPlansLog').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.primary=this.selectedResult['primarys'];
      this.secondary=this.selectedResult['secondary']
   this.tertiary=this.selectedResult['tertiary']
    this.isView=true;
  }


}
