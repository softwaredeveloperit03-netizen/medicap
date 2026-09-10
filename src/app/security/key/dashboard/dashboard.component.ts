import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master', title: 'Key Master', route: '/security/key/master', icon: 'fa-key', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'register', title: 'Key Register', route: '/security/key/register', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'new', title: 'Issue Key', route: '/security/key/new', icon: 'fa-plus-circle', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    { id: 'form', title: 'Return Key', route: '/security/key/form', icon: 'fa-undo', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
  ];


  constructor() { }

  ngOnInit(): void {
  }

}
