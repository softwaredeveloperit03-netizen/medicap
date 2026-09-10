import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

 
  isView = false;
  results;
  departments;
  department_name = '';
  from_date = '';
  to_date = '';

  selectedResult = [];
  constructor(private service:DataAccessService,private datePip:DatePipe) { 
    this.from_date = this.datePip.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePip.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getMaintenanceLog();
    this.getDepartments();
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getMaintenanceLog(){
    this.service.get('engineering/maintenance.php?type=getMaintenanceLog&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(){
    this.service.open('qms/maintenance.php?type=downloadMaintenanceLog');
  }
}
