import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  selectresult=[];
  isView=false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
   this.getProcesses()
  }
  getProcesses(){
    this.service.get('bmr/process.php?type=getProcesses').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectresult = this.results[index];
    this.isView = true;
  }

}
