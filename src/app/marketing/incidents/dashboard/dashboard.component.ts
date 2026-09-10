import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'incidents-form', title: 'New Incident', route: 'incidents/form', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'incidents-checker', title: 'Incident for Checking', route: 'incidents/checker', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'incidents-log', title: 'Incident Log', route: 'incidents/log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'incidents-trend', title: 'Incident Trend', route: 'incidents/trend', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
  ];


  isApprover;
  isChecker;

  constructor() { }

  ngOnInit() {

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

}
