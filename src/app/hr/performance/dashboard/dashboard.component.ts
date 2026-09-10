import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master', title: 'Master Checklist', route: 'master', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'initiate', title: 'Initiate Appraisal', route: 'initiate', icon: 'fa-user-plus', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'goal', title: 'Goal Set. & Appr.', route: 'goal', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'feedback', title: '1st Level(Emp. Feed.)', route: 'feedback', icon: 'fa-comment-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'ordinate', title: '2nd Level(Sub-Ord.)', route: 'ordinate', icon: 'fa-user-friends', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'functional', title: '3rd Level(Cross-fun.)', route: 'functional', icon: 'fa-sitemap', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'customers', title: '4th Level(Customers)', route: 'customers', icon: 'fa-users', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'superrior', title: '5th Level(Sup.)', route: 'superrior', icon: 'fa-user-tie', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'management', title: '6th Level(Mgt)', route: 'management', icon: 'fa-user-cog', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'report1', title: 'Appraisal Report', route: 'report1', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'team', title: 'Appraisal By HR Team', route: 'team', icon: 'fa-users-cog', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'goal', title: 'Goal Setting & Approval', route: 'goal', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'feedback', title: '1st Level(Employee Feedback)', route: 'feedback', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'ordinate', title: '2nd Level(Sub-Ordinate)', route: 'ordinate', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'functional', title: '3rd Level(Cross-functional)', route: 'functional', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'superrior', title: '5th Level(Superrior)', route: 'superrior', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'management', title: '6th Level(Management)', route: 'management', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
