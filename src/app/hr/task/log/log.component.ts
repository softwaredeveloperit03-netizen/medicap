import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView=false;
  results;
  employeeList;
  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTasks();
    this.getEmployees();
  }

  getTasks(){
    this.service.get('hr/task.php?type=getTasks').subscribe((response: any)=> {
      this.results = response;
    });
  }

  viewtask(url) {
 
    url = this.service.url + '../../upload/task/' + url;
    window.open(url, '_blank');
  }

  downloadEmpForm() {
    this.service.open('hr/employee.php?type=download_task&id=' + this.selectedReport['id']);
  }


  view(index){
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  getEmployees(){
    this.service.get('hr/task.php?type=getEmployees').subscribe((response:any) =>{
      this.employeeList = response;
    });
  }
}
