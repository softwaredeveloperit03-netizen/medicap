import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-todays',
  templateUrl: './todays.component.html',
  styleUrls: ['./todays.component.css']
})
export class TodaysComponent implements OnInit {
  isView=false;
  results;
  employeeList;
  selectedReport=[];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTodaysTasks();
    this.getEmployees();
  }
  
  getTodaysTasks(){
    this.service.get('hr/task.php?type=getTodaysTasks').subscribe(response=>{
      this.results = response;
    });
  }
  getEmployees(){
    this.service.get('hr/task.php?type=getEmployees').subscribe((response:any) =>{
      this.employeeList = response;
    });
  }
  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }

}
