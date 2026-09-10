import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'depthead', title: 'Concern HOD Comment', route: 'deptHead', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'approvalofchangebyqahead', title: 'Approval Of Change', route: 'approvalOfChangeByQaHead', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'closedbyqahead', title: 'Review Of Change', route: 'closedByQaHead', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'ccveriandeff', title: 'CC Closure QA Head', route: 'ccVeriAndEff', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'actionplan', title: 'ACTION PLAN & APPROVAL', route: 'Actionplan', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'qaheadreview', title: 'QA Head – Review & Assign', route: 'qaHeadReview', icon: 'fa-user-check', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  constructor() { }
  Department = localStorage.getItem('department');

  ngOnInit(): void {
    this.Department = localStorage.getItem('department');

  }

}
