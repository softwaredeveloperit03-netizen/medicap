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

  category = '';
  from_date = '';
  to_date = '';

  constructor(private service: DataAccessService, private router: Router, private datePipe:DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.getIncidentsLog();
  }

  getIncidentsLog() {
    this.service.get('qms/incident.php?type=getIncidentLog&category=' + this.category + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }


  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  close(){
    this.router.navigate(['/']);
  }
  download(){
    this.service.open('qms/incident.php?type=downloadIncidentLog&category=' + this.category + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }
  downloadp(){
    this.service.open('qms/incident.php?type=downloadIncident&incident_no='+this.selectedReport['incident_no'])
  }

}
