import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'concern-hod-comment', title: 'Concern Hod Comment', route: 'Concern-Hod-Comment', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qareview', title: 'Reviewed By QA Manager', route: 'qaReview', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'devassassment', title: 'Deviation Assessment', route: 'devAssassment', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'apprvlofqa', title: 'Closure Of Deviation', route: 'apprvlOfQa', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'devassassment', title: 'Concern Hod Comment', route: 'devAssassment', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'closure', title: 'Closure Of Deviation', route: 'closure', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];

  department = '';
  constructor() { }

  ngOnInit(): void {
    this.department = localStorage.getItem('department');
  }

}
