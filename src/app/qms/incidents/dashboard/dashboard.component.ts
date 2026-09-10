import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'form', title: 'New Incident', route: 'form', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'checker', title: 'Incident for Checking', route: 'checker', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'log', title: 'Incident Log', route: 'log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'trend', title: 'Incident Trend', route: 'trend', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'resignation', title: 'resignations', route: 'resignation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
  ];


  isApprover;
  isChecker;

  constructor(private router: Router) { }

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
  close() {
    this.router.navigate(['/']);
  }

}
