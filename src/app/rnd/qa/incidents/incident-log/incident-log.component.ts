import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-incident-log',
  templateUrl: './incident-log.component.html',
  styleUrls: ['./incident-log.component.css'],
  providers:[DatePipe]
})
export class IncidentLogComponent implements OnInit {
  isView = false;
  results;
  departments;
  selectedReport = [];

  department_name = '';
  category = '';
  fromdate = '';
  todate = '';

  constructor(private service: DataAccessService, private router: Router, private datePipe:DatePipe) { 
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.getDepartments();
    this.getIncidentsLog();
  }

  getIncidentsLog() {
    this.service.get('qa/incident.php?type=getIncidentsLog&department_name=' + this.department_name + '&category=' + this.category + '&fromdate=' + this.fromdate + '&todate=' + this.todate).subscribe(response => {
      this.results = response;
    });
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  close(){
    this.router.navigate(['/']);
  }



}
