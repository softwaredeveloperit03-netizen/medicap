import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-incidents',
  templateUrl: './incidents.component.html',
  styleUrls: ['./incidents.component.css']
})
export class IncidentsComponent implements OnInit {

  isView = false;
  incidents;
  selectedReport = [];

  incident = [];
  isApprover;
  isChecker;
  constructor(private service: DataAccessService, private router: Router) { }


  ngOnInit(): void {
    this.getPendingIncidents();

    if(localStorage.getItem('approver') == 'true') {
      this.isApprover =  true;
    } else {
      this.isApprover =  false;
    }

    if(localStorage.getItem('checker') == 'true') {
      this.isChecker =  true;
    } else {
      this.isChecker =  false;
    }

  }

  getPendingIncidents() {
    this.service.get('qa/incident.php?type=getAllIncident').subscribe(response => {
      this.incidents = response;
    });
  }

  viewIncident(index) {
    this.selectedReport = this.incidents[index];
    this.isView = true;
  }

  close(){
    this.router.navigate(['/']);
  }

}
