import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
 
})
export class DashboardComponent implements OnInit {
  result;
  selectedReport=[];
  isView=false;
 
  constructor(private service: DataAccessService ) {  }
  
  ngOnInit(): void {
  
    this.getIssueLog();
  }

  getIssueLog(){
    this.service.get('store/jobwork.php?type=getJobworkLog').subscribe(response =>{
      this.result =response;
    });
  }

  view(index){
    this.selectedReport =  this.result[index];
    this.isView = true;
  }

 
}