import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'identification', title: 'cGMP Identification', route: 'identification', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'schedule', title: 'cGMP Schedule', route: 'schedule', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'annocement', title: 'cGMP Announcement', route: 'annocement', icon: 'fa-bullhorn', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'attendance', title: 'cGMP Attendance', route: 'attendance', icon: 'fa-certificate', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'questions', title: 'Evaluation', route: 'questions', icon: 'fa-certificate', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'log', title: 'cGMP Log', route: 'log', icon: 'fa-poll', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'feedback', title: 'FeedBack', route: 'feedback', icon: 'fa-poll', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
