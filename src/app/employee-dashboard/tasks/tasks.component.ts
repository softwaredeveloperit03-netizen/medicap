import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-tasks',
  templateUrl: './tasks.component.html',
  styleUrls: ['./tasks.component.css']
})
export class TasksComponent implements OnInit {
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
    this.service.get('hr/task.php?type=myTodaysTasks').subscribe(response=>{
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
  vfrom_time;
vto_time;
  getCurrentTime(action, value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    /* let time = new Date().toLocaleTimeString(); */
    if (value == "usages") {
      if (action == 'from_time') {
        this.vfrom_time = h + ':' + m;
      } else {
        this.vto_time = h + ':' + m;
      }
    } 

    this.service.get('hr/task.php?type=myTodaysTasks_time&start_time='+this.vfrom_time+'&id='+this.selectedReport['id']).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Dedusting record saved Successfully');       
        this.getTodaysTasks();
        this.isView=false;
      } else {
        alertify.error('An error occured');
      }
    });

  }
  getCurrentTime2(action, value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    /* let time = new Date().toLocaleTimeString(); */
    if (value == "usages") {
      if (action == 'from_time') {
        this.vfrom_time = h + ':' + m;
      } else {
        this.vto_time = h + ':' + m;
      }
    } 

    this.service.get('hr/task.php?type=myTodaysTasks_time2&end_time='+this.vto_time+'&id='+this.selectedReport['id']).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Dedusting record saved Successfully');       
        this.getTodaysTasks();
        this.isView=false;
      } else {
        alertify.error('An error occured');
      }
    });

  }

}
