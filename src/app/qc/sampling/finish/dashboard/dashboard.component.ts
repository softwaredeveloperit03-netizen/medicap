import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'allocation', title: 'Sampling Allocation', route: 'allocation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'new', title: 'New Sampling', route: 'new', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'inprocess', title: 'Samplings for Checking', route: 'inprocess', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Samplings for Approval', route: 'checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'log', title: 'Samplings Report', route: 'log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'sample-quantity', title: 'Sampling Quantity Master', route: 'sample_quantity', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
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
