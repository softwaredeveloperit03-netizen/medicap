import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'moanalysis', title: 'Raw Material', route: 'moanalysis', icon: 'fa-cube', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'packing-new', title: 'Packing Material', route: 'packing/new', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'finish', title: 'Finish Product', route: 'finish', icon: 'fa-archive', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'inprocess', title: 'Inprocess', route: 'inprocess', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'water-new', title: 'Water', route: 'water/new', icon: 'fa-tint', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'methods', title: 'Method Master', route: 'methods', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'raw', title: 'Raw Material', route: 'raw', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'retest', title: 'Retest', route: 'retest', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'stability', title: 'Stability', route: 'stability', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
  ];


  isUser = false;
  isChecker = false;
  isApprover = false;
  constructor() {
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit() {
  }

}
