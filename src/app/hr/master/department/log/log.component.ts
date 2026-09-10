import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getDepartment();
  }
  getDepartment(){
    this.service.get('hr/department.php?type=getDepartments').subscribe(response=>{
      this.results=response;
    })
  }
}
