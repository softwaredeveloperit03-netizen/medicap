import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'annoucement', title: 'Daily Training Announcement', route: 'annoucement', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'annoucement-approval', title: 'Daily Training Announcement Approval', route: 'annoucement-approval', icon: 'fa-calendar-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'log', title: 'Log', route: 'log', icon: 'fa-clipboard', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];


  isUser = false;
  isChecker = false;
  isApprover = false;
  constructor() {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit(): void {
  }

}
